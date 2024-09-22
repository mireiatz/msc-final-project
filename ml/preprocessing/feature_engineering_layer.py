import pandas as pd
from datetime import datetime, timedelta
import numpy as np
from sklearn.preprocessing import LabelEncoder, MinMaxScaler
from datetime import datetime
import os

class FeatureEngineeringLayer:

    def __init__(self, data, mapping_dir='./ml/data/mappings/'):
        self.data = data
        self.label_encoder = LabelEncoder()
        self.scaler = MinMaxScaler()
        self.mapping_dir = mapping_dir

    def load_mapping(self, feature):
        """
        Load feature mapping from a CSV file if it exists.
        """
        mapping_path = os.path.join(self.mapping_dir, f'{feature}_map.csv')  # Define the path

        # Read and return the mapping of the feature
        if os.path.exists(mapping_path):
            return pd.read_csv(mapping_path).set_index(feature).to_dict()[f'{feature}_encoded']

        return {}

    def save_mapping(self, feature, mapping):
        """
        Save updated feature mapping to a CSV file.
        """
        mapping_path = os.path.join(self.mapping_dir, f'{feature}_map.csv')  # Define the path

        # Save the mapping in a file as a DataFrame
        mapping_df = pd.DataFrame(list(mapping.items()), columns=[feature, f'{feature}_encoded'])
        mapping_df.to_csv(mapping_path, index=False)

    def encode_categorical_features(self, df, feature):
        """
        Apply Label Encoding to convert categorical features like 'product_id' and 'category' into numerical values.
        """
        # Load existing mapping from the feature if it exists
        mapping = self.load_mapping(feature)

        # Map existing values
        df[f'{feature}_encoded'] = df[feature].map(mapping)

        # Find unmapped values
        unmapped_values = df[feature][df[f'{feature}_encoded'].isna()].unique()

        # If there are unmapped values, assign unmapped encodings
        if len(unmapped_values) > 0:
            max_existing_encoding = max(mapping.values()) if mapping else 0  # Begin after the highest code
            unmapped_encodings = {value: max_existing_encoding + idx + 1 for idx, value in enumerate(unmapped_values)}

            # Update the mapping with unmapped encodings
            mapping.update(unmapped_encodings)

            # Map the unmapped values in the DataFrame
            df.loc[df[f'{feature}_encoded'].isna(), f'{feature}_encoded'] = df[feature].map(unmapped_encodings)

            # Save the updated mapping
            self.save_mapping(feature, mapping)

        # Ensure the values are integers
        df[f'{feature}_encoded'] = df[f'{feature}_encoded'].astype(int)

        return df

    def cyclic_encoding(self, value, max_value):
        """
        Perform cyclic encoding for a value (e.g., month or weekday).
        """
        sin_value = np.sin(2 * np.pi * value / max_value)  # sine transformation
        cos_value = np.cos(2 * np.pi * value / max_value)  # cosine transformation

        return round(sin_value, 2), round(cos_value, 2)

    def extract_date_features(self, year, week, day_name):
        """
        Expand the date information from the given year, week, and day of the week by creating numerical date-related features,
        including cyclic encoding for weekday and month.
        """
        # Map day names to offset indices (0 = Monday - 6 = Sunday)
        day_map = {'monday': 0, 'tuesday': 1, 'wednesday': 2, 'thursday': 3, 'friday': 4, 'saturday': 5, 'sunday': 6}
        day_offset = day_map[day_name.lower()]

        # Get the date and extract the day and month
        date = datetime.strptime(f'{year}-W{int(week)}-1', "%Y-W%W-%w") + timedelta(days=day_offset)
        day_of_month = date.day
        month = date.month

        # Cyclic encoding for month (12 months in a year)
        month_sin, month_cos = self.cyclic_encoding(month, 12)

        # Cyclic encoding for weekday (7 days in a week)
        weekday_sin, weekday_cos = self.cyclic_encoding(day_offset, 7)

        return date, day_offset, day_of_month, month, month_sin, month_cos, weekday_sin, weekday_cos

    def pivot_weekly_data(self, df):
        """
        Pivot the weekly data into daily records.
        """
        daily_rows = []
        day_columns = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']

        # Iterate over each row in the DataFrame
        for _, row in df.iterrows():
            year = row['year']
            week = row['week']
            product_id = row['product_id']
            product_id_encoded = row['product_id_encoded']
            product_name = row['product_name']
            category = row['category']
            category_encoded = row['category_encoded']
            in_stock = row['in_stock']

            # Calculate per-item value if quantity is greater than 0
            per_item_value = round(row['value'] / row['quantity'], 2) if row['quantity'] > 0 else 0

            # Generate a row for each day of the week
            for day in day_columns:
                quantity = row[day]

                # Extract the date features for the day
                date, day_offset, day_of_month, month, month_sin, month_cos, weekday_sin, weekday_cos = self.extract_date_features(year, week, day)

                daily_rows.append({
                    'product_id': product_id,
                    'product_id_encoded': product_id_encoded,
                    'product_name': product_name,
                    'category': category,
                    'category_encoded': category_encoded,
                    'quantity': quantity,
                    'per_item_value': per_item_value,
                    'in_stock': in_stock,
                    'date': date,
                    'day_offset': day_offset,
                    'day_of_month': day_of_month,
                    'year': year,
                    'month': month,
                    'month_sin': month_sin,
                    'month_cos': month_cos,
                    'weekday_sin': weekday_sin,
                    'weekday_cos': weekday_cos
                })

        return pd.DataFrame(daily_rows)

    def adjust_in_stock_features(self, df):
        """
        Adjust the stock status for each product.
        """
        # Sort data per product and date
        df = self.sort_by_columns(df)

        # Shift 'in_stock' values by 7 days (1 week) to adjust based on previous week's stock
        df['in_stock'] = df.groupby(['product_id'])['in_stock'].shift(7).fillna(df['in_stock']).astype(int)

        # Ensure 'in_stock' is 1 if the product had sales on that day
        df['in_stock'] = df.apply(lambda row: 1 if row['quantity'] > 0 else row['in_stock'], axis=1).astype(int)

        # Create flag feature to highlight instances with stock and no sales
        df['in_stock_no_sales'] = df.apply(
            lambda row: 1 if (row['in_stock'] == 1 and row['quantity'] == 0) else 0,
            axis=1
        )

        return df

    def add_holiday_flag(self, df):
        """
        Add a column indicating whether a date is considered a holiday.
        """
        # Define holiday periods
        holiday_periods = [
            ((7, 26), (8, 1)),  # Specific holiday (July 26 to August 1)
            ((10, 24), (10, 31)),  # Halloween (October 24 to October 31)
            ((12, 18), (12, 24)),  # Christmas (December 18 to December 24)
            ((3, 28), (4, 4))  # Easter (March 28 to April 4)
        ]

        # Set holiday flag to false for all rows
        df['is_holiday'] = 0

        # Set the holiday flag to true if the date is within the holiday periods
        for (start_month, start_day), (end_month, end_day) in holiday_periods:
            if start_month == end_month:
                # For holidays within the same month
                mask = (df['month'] == start_month) & (df['day_of_month'].between(start_day, end_day))
            else:
                # For holidays spanning two different months
                mask = (
                    ((df['month'] == start_month) & (df['day_of_month'] >= start_day)) |
                    ((df['month'] == end_month) & (df['day_of_month'] <= end_day)) |
                    ((df['month'] > start_month) & (df['month'] < end_month))
                )
            df['is_holiday'] |= mask.astype(int)

        return df

    def sort_by_columns(self, df, columns=['year', 'month', 'day_of_month']):
        """
        Sort the data by the specified columns (date as default).
        """

        return df.sort_values(by=columns)

    def create_lag_features(self, df, column, lag_days=[7, 30, 365]):
        """
        Create lagged columns for a given column across specified days.
        """
        if column not in df.columns:
            raise ValueError(f"'{column}' column not found in DataFrame.")

        # Sort data per product and date
        df = self.sort_by_columns(df)

        # Create lag columns for each specified amount of days
        for lag in lag_days:
            lag_col_name = f'{column}_lag_{lag}'

            # Group by 'product_id' and shift by the specified lag days
            df[lag_col_name] = df.groupby('product_id')[column].shift(lag).fillna(0).astype(int)

        return df

    def create_rolling_avg_features(self, df, column, windows=[1, 7, 30, 365]):
        """
        Create rolling average columns for the given column based on specified windows.
        """
        if column not in df.columns:
            raise ValueError(f"'{column}' column not found in DataFrame.")

        # Sort data per product and date
        df = self.sort_by_columns(df)

        # Create rolling columns for each window
        for window in windows:
            rolling_col_name = f'{column}_rolling_avg_{window}'

            # Apply rolling mean within each 'product_id' group
            df[rolling_col_name] = df.groupby('product_id')[column].transform(
                lambda x: x.rolling(window, min_periods=1).mean()
            )
            df[rolling_col_name] = df[rolling_col_name].fillna(0).round(2)

        return df

    def merge_with_historical_data(self, df, historical_data):
        """
        Merge current DataFrame with preprocessed historical data.
        """

        return pd.concat([historical_data, df], ignore_index=True)

    def process(self, historical_data=None):
        """
        Re-structure the DataFrame and create new features:
        - Encode categories and product IDs.
        - Pivot weekly data to daily.
        - Adjust the stock status.
        - Add a holiday flag.
        - Merge with historical data if it exists.
        - Create lag and rolling columns.
        """
        print(f"Encode categories at {datetime.now()}")
        df = self.encode_categorical_features(self.data, 'category')

        print(f"Encode product ids at {datetime.now()}")
        df = self.encode_categorical_features(df, 'product_id')

        print(f"Pivot at {datetime.now()}")
        df = self.pivot_weekly_data(df)

        print(f"Adjust in stock at {datetime.now()}")
        df = self.adjust_in_stock_features(df)

        print(f"Add holiday flag at {datetime.now()}")
        df = self.add_holiday_flag(df)

        print(f"Merge with historical data at {datetime.now()}")
        if historical_data is not None:
            df = self.merge_with_historical_data(df, historical_data)

        print(f"Create lag columns at {datetime.now()}")
        df = self.create_lag_features(df, 'quantity')

        print(f"Create rolling columns at {datetime.now()}")
        df = self.create_rolling_avg_features(df, 'quantity')

        return df
