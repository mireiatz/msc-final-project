import os
import pandas as pd
import numpy as np
import logging
from datetime import datetime, timedelta
from sklearn.preprocessing import LabelEncoder, MinMaxScaler

class FeatureEngineeringLayer:

    def __init__(self, data, mapping_dir='./ml/data/mappings/', historical_data_path='./ml/data/historical/processed/processed_data.csv', days=35):
        self.data = data
        self.label_encoder = LabelEncoder()
        self.scaler = MinMaxScaler()
        self.mapping_dir = mapping_dir
        self.historical_data_path = historical_data_path
        self.historical_data_fetched = False
        self.days = days

    def load_mapping(self, feature):
        """
        Load the mapping for a specific feature from a CSV file, if it exists.
        """
        # Save to a file with the name of the feature
        mapping_path = os.path.join(self.mapping_dir, f'{feature}_map.csv')

        # Read and return the mapping of the feature
        if os.path.exists(mapping_path):
            return pd.read_csv(mapping_path).set_index(feature).to_dict()[f'{feature}_encoded']

        logging.info(f"Mapping loaded for {feature}")

        return {}

    def save_mapping(self, feature, mapping):
        """
        Save the updated mapping for a specific feature to a CSV file.
        """
        # Save to a file with the name of the feature
        mapping_path = os.path.join(self.mapping_dir, f'{feature}_map.csv')

        # Convert mapping dictionary to DataFrame and save to CSV
        mapping_df = pd.DataFrame(list(mapping.items()), columns=[feature, f'{feature}_encoded'])
        mapping_df.to_csv(mapping_path, index=False)

        logging.info(f"Mapping saved for {feature}")

    def encode_categorical_features(self, df, feature):
        """
        Apply Label Encoding to convert categorical features like 'product_id' and 'category' into numerical values, reusing existing mappings and assigning new encodings to previously unseen values.
        """
        # Load existing mapping if it exists
        mapping = self.load_mapping(feature)

        # Map existing values
        df[f'{feature}_encoded'] = df[feature].map(mapping)

        # Identify new values that need to be encoded
        unmapped_values = df[feature][df[f'{feature}_encoded'].isna()].unique()

        # Assign new encodings to the unmapped values
        if len(unmapped_values) > 0:
            # Determine the starting value for new encodings
            max_existing_encoding = max(mapping.values()) if mapping else 0

            # Assign new encoding starting from the last used value
            new_encodings = {value: max_existing_encoding + idx + 1 for idx, value in enumerate(unmapped_values)}

            # Update the mapping with the new encodings
            mapping.update(new_encodings)

            # Apply the new encodings to the DataFrame
            df.loc[df[f'{feature}_encoded'].isna(), f'{feature}_encoded'] = df[feature].map(new_encodings)

            # Save the updated mapping
            self.save_mapping(feature, mapping)

        # Ensure all encoded values are integers
        df[f'{feature}_encoded'] = df[f'{feature}_encoded'].astype(int)

        logging.info(f"'{feature}' encoded")

        return df

    def pivot_weekly_data(self, df):
        """
        Pivot the weekly data into daily records.
        """
        # Define the columns representing the weekdays
        day_columns = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']

        # Iterate through the weeks
        daily_rows = []
        for _, row in df.iterrows():
            # Extract shared values from the row
            year = row['year']
            week = row['week']
            product_id = row['product_id']
            product_name = row['product_name']
            category = row['category']
            product_id_encoded = row['product_id_encoded']
            category_encoded = row['category_encoded']
            in_stock = row['in_stock']

            # Calculate per item value for the entire week
            per_item_value = round(row['value'] / row['quantity'], 2) if row['quantity'] > 0 else 0

            # Iterate over each day of the week and create a daily record
            for day in day_columns:
                daily_quantity = row[day]

                daily_rows.append({
                    'product_id': product_id,
                    'product_name': product_name,
                    'category': category,
                    'product_id_encoded': product_id_encoded,
                    'category_encoded': category_encoded,
                    'quantity': daily_quantity,
                    'per_item_value': per_item_value,
                    'in_stock': in_stock,
                    'year': year,
                    'week': week,
                    'weekday': day
                })

        # Convert the list of daily records into a DataFrame
        df = pd.DataFrame(daily_rows)

        logging.info("Weekly records pivoted to daily")

        return df

    def cyclic_encoding(self, value, max_value):
        """
        Perform cyclic encoding for a value (e.g., month or weekday).
        """
        sin_value = np.sin(2 * np.pi * value / max_value)  # sine transformation
        cos_value = np.cos(2 * np.pi * value / max_value)  # cosine transformation

        return round(sin_value, 2), round(cos_value, 2)

    def create_date_features(self, df):
        """
        Create date-related features.
        """
        # Map day names to offset indices (0 = Monday - 6 = Sunday)
        day_map = {'monday': 0, 'tuesday': 1, 'wednesday': 2, 'thursday': 3, 'friday': 4, 'saturday': 5, 'sunday': 6}

        # Apply the transformation to generate date-related features
        def generate_date_features(row):
            year = row['year']
            week = row['week']
            weekday = day_map[row['weekday'].lower()]  # Encode the weekday

            # Calculate the date
            date = datetime.strptime(f'{year}-W{int(week)}-1', "%Y-W%W-%w") + timedelta(days=weekday)

            # Extract 'day_of_month' and 'month'
            day_of_month = date.day
            month = date.month

            # Cyclic encoding for month and weekday
            month_sin, month_cos = self.cyclic_encoding(month, 12)
            weekday_sin, weekday_cos = self.cyclic_encoding(weekday, 7)

            # Return all the date-related features
            return pd.Series([date, weekday, day_of_month, month, month_sin, month_cos, weekday_sin, weekday_cos])

        # Apply the date feature generation to each row
        df[['date', 'weekday', 'day_of_month', 'month', 'month_sin', 'month_cos', 'weekday_sin', 'weekday_cos']] = df.apply(generate_date_features, axis=1)

        logging.info("Create date features")

        return df

    def adjust_in_stock(self, df):
        """
        Adjust the stock status for each product based on previous week's stock.
        """
        # Ensure 'date' is in datetime format and sort it
        if not pd.api.types.is_datetime64_any_dtype(df['date']):
            df['date'] = pd.to_datetime(df['date'], format='%Y-%m-%d')
        df = df.sort_values(by=['product_id', 'date'])

        # Shift 'in_stock' values by 1 week
        df['in_stock_shifted'] = df.groupby('product_id')['in_stock'].shift(7)

        # Fill missing shifted values with the original 'in_stock' values
        df['in_stock'] = df['in_stock_shifted'].fillna(df['in_stock']).astype(int)

        df.loc[df['quantity'] > 0, 'in_stock'] = 1

        # Drop the temporary 'in_stock_shifted' column
        df.drop(columns=['in_stock_shifted'], inplace=True)

        logging.info("Stock status adjusted")

        return df

    def fetch_historical_data(self, last_date):
        """
        Fetch historical data from the preprocessed historical dataset based on the given date.
        """
        try:
            # Load the preprocessed historical data
            historical_data = pd.read_csv(self.historical_data_path, parse_dates=['date'])

            if historical_data.empty:
                return pd.DataFrame()

            # Filter historical data based on the 'self.days' threshold
            start_date = last_date - pd.to_timedelta(self.days, unit='D')
            df = historical_data[historical_data['date'] >= start_date]

            logging.info("Historical data fetched")

            return df

        except FileNotFoundError:
            logging.info(f"Historical data file not found for merging at {self.historical_data_path}")
            return pd.DataFrame()

    def merge_historical_data(self, df):
        """
        Merge current DataFrame with preprocessed historical data.
        """
        df['date'] = pd.to_datetime(df['date'], format='%Y-%m-%d', errors='coerce')

        # Fetch the data
        historical_data = self.fetch_historical_data(df['date'].min())

        # If historical data exists, concatenate with current data
        if not historical_data.empty:
            df = pd.concat([historical_data, df], ignore_index=True)
            self.historical_data_fetched = True

            logging.info("Historical data merged")

        return df

    def create_time_series_features(self, df, column, periods=[1, 7, 14, 30, 90, 365]):
        """
        Create lag and rolling average columns for a given feature, e.g., 'quantity' across specified days.
        """
        # Ensure 'date' is in datetime format, sort it and group it
        if not pd.api.types.is_datetime64_any_dtype(df['date']):
            df['date'] = pd.to_datetime(df['date'], format='%Y-%m-%d')
        df = df.sort_values(by=['product_id', 'date'])
        grouped = df.groupby('product_id')

        # Loop through defined periods of time
        for period in periods:
            # Create lags
            lag_col_name = f'{column}_lag_{period}'
            df[lag_col_name] = grouped[column].shift(period)

            # Create rolling averages, except for 1 day period
            if period != 1:
                rolling_col_name = f'{column}_rolling_avg_{period}'
                df[rolling_col_name] = grouped[column].transform(lambda x: x.rolling(period, min_periods=1).mean())

        # Fill missing values with 0
        df.fillna(0, inplace=True)

        logging.info("Time series features created")

        return df

    def remove_historical_data(self, df):
        """
        Remove historical data rows older than the 'self.days' threshold from the current dataset.
        """
        # Remove historical data if it was fetched
        if self.historical_data_fetched:
            # Calculate the cutoff date based on the minimum date in the current data
            cutoff_date = df['date'].min() + pd.to_timedelta(self.days, unit='D')

            # Filter out rows older than the cutoff date
            df = df[df['date'] >= cutoff_date].reset_index(drop=True)

            logging.info("Historical data removed")

        return df

    def process(self):
        """
        Re-structure the DataFrame and create new features.
        """
        logging.info("Starting feature engineering process...")

        df = self.encode_categorical_features(self.data, 'category')
        df = self.encode_categorical_features(df, 'product_id')
        df = self.pivot_weekly_data(df)
        df = self.create_date_features(df)
        df = self.adjust_in_stock(df)
        df = self.merge_historical_data(df)
        df = self.create_time_series_features(df, 'quantity')
        df = self.remove_historical_data(df)

        return df
