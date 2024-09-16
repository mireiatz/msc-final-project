import pandas as pd
from datetime import datetime, timedelta
import numpy as np
from sklearn.preprocessing import LabelEncoder, MinMaxScaler
from datetime import datetime

class FeatureEngineeringLayer:

    def __init__(self, data, output_path):
        self.data = data
        self.output_path = output_path
        self.label_encoder = LabelEncoder()
        self.scaler = MinMaxScaler()

    def encode_categories(self, df):
        """
        Apply Label Encoding to convert categories into numerical values.
        """
        df['category_encoded'] = self.label_encoder.fit_transform(df['category'])

        return df

    def cyclic_encoding(self, value, max_value):
        """
        Perform cyclic encoding for a value (e.g., month or weekday).
        """
        sin_value = np.sin(2 * np.pi * value / max_value)
        cos_value = np.cos(2 * np.pi * value / max_value)

        return round(sin_value, 2), round(cos_value, 2)

    def extract_date_features(self, year, week, day_name):
        """
        Expand the date information from the given year, week, and day of the week by creating numerical date-related features, including cyclic encoding for weekday and month.
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

        # Iterate over each row in the dataframe
        for _, row in df.iterrows():
            year = row['year']
            week = row['week']
            product_id = row['product_id']
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

    def adjust_in_stock(self, df):
        """
        Adjust the 'in_stock' status for each product.
        """
        # Sort data per product and date
        df = self.sort_by_columns(df)

        # Shift 'in_stock' values by 7 days (1 week) to adjust based on previous week's stock
        df['in_stock'] = df.groupby(['product_id'])['in_stock'].shift(7).fillna(df['in_stock']).astype(int)

        # Ensure 'in_stock' is 1 if the product had sales on that day
        df['in_stock'] = df.apply(lambda row: 1 if row['quantity'] > 0 else row['in_stock'], axis=1).astype(int)

        return df

    def add_holiday_flag(self, df):
        """
        Add a column indicating whether a date is considered a holiday.
        """
        # Define holiday periods
        holiday_periods = [
            ((7, 26), (8, 1)),  # Specific holiday (July 26 to August 1)
            ((10, 24), (10, 31)),  # Halloween (October 24 to October 31)
            ((12, 11), (12, 24)),  # Christmas (December 11 to December 24)
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

    def create_lag_columns(self, df, column, lag_days=[1, 7, 30, 365]):
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

    def create_rolling_avg_columns(self, df, column, windows=[7, 30, 365]):
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

    def scale_features(self, df):
        """
        Apply Min-Max Scaling to numerical columns to normalise them.
        """
        cols_to_scale = [
            'quantity_lag_1', 'quantity_lag_7', 'quantity_lag_30', 'quantity_lag_365',
            'quantity_rolling_avg_7', 'quantity_rolling_avg_30', 'quantity_rolling_avg_365',
            'per_item_value', 'month_sin', 'month_cos', 'weekday_sin', 'weekday_cos'
        ]

        # Apply scaling to the defined columns
        df[cols_to_scale] = self.scaler.fit_transform(df[cols_to_scale])

        return df

    def save_data(self, df):
        """Save the completed DataFrame to a CSV file."""
        df.to_csv(self.output_path, index=False)

    def process(self):
        """
        Re-structure the DataFrame and create new features:
        - Encode categories.
        - Pivot weekly data.
        - Adjust the stock data.
        - Add a holiday flag.
        - Create lag and rolling columns.
        - Save the data into a file.
        """
        print(f"Encode categories at {datetime.now()}")
        df = self.encode_categories(self.data)
        print(f"Pivot at {datetime.now()}")
        df = self.pivot_weekly_data(df)
        print(f"Adjust in stock at {datetime.now()}")
        df = self.adjust_in_stock(df)
        print(f"Add holiday flag at {datetime.now()}")
        df = self.add_holiday_flag(df)
        print(f"Create lag columns at {datetime.now()}")
        df = self.create_lag_columns(df, 'quantity')
        print(f"Create rolling columns at {datetime.now()}")
        df = self.create_rolling_avg_columns(df, 'quantity')
        print(f"Scaling features at {datetime.now()}")
        df = self.scale_features(df)
        print(f"Save data at {datetime.now()}")
        self.save_data(df)

        return df
