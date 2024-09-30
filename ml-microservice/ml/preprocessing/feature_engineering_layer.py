import logging
import os
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
from sklearn.preprocessing import LabelEncoder, MinMaxScaler

class FeatureEngineeringLayer:

    def __init__(self, data, mapping_dir='./ml/data/mappings/', historical_data_path='./ml/data/historical/processed/processed_data.csv', days=365):
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

        # Check if the file exists
        if os.path.exists(mapping_path):
            try:
                # Try loading the CSV into a DataFrame and converting it to a dictionary
                mapping = pd.read_csv(mapping_path).set_index(feature).to_dict()[f'{feature}_encoded']
                logging.info(f"Mapping loaded for {feature}")
                return mapping
            except Exception as e:
                logging.error(f"Error loading mapping for {feature}: {e}")
                return {}
        else:
            logging.warning(f"Mapping file not found for {feature}: {mapping_path}")
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

    def encode_categorical_feature(self, df, feature):
        """
        Apply Label Encoding to convert categorical features like 'product_id' and 'category' into numerical values, reusing existing mappings and assigning new encodings to previously unseen values.
        """
        # Load existing mapping if it exists
        mapping = self.load_mapping(feature)

        if not mapping:
            logging.warning(f"No existing mapping found for {feature}, starting fresh.")

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
            original_product_id = row['original_product_id']
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
                    'original_product_id': original_product_id,
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

    def create_periodic_features(self, df):
        """
        Create the year feature.
        """
        if 'year' not in df.columns:
            df['year'] = df['date'].dt.year

        return df

    def apply_cyclic_encoding(self, df, column, max_value):
        """
        Apply cyclic encoding for a specific column in a DataFrame.
        """
        df['sin_' + column], df['cos_' + column] = self.cyclic_encoding(df[column], max_value)

        return df

    def create_date_feature(self, df):
        """
        Create the date feature.
        """
        if 'date' not in df.columns:
            # Ensure 'year' and 'week' are integers
            df['year'] = df['year'].astype(int)
            df['week'] = df['week'].astype(int)

            # Vectorized date construction using pandas' datetime module
            df['date'] = pd.to_datetime(df['year'].astype(str) + '-W' + df['week'].astype(str) + '-1', format='%Y-W%W-%w') + pd.to_timedelta(df['weekday'], unit='D')

        df['date'] = pd.to_datetime(df['date'])

        return df

    def create_week_features(self, df):
        """
        Create week-related features.
        """
        if 'week' not in df.columns:
            df['date'] = pd.to_datetime(df['date'], errors='coerce')
            df['week'] = df['date'].dt.isocalendar().week

        # Map day names to offset indices (0 = Monday - 6 = Sunday)
        day_map = {'monday': 0, 'tuesday': 1, 'wednesday': 2, 'thursday': 3, 'friday': 4, 'saturday': 5, 'sunday': 6}

        if 'weekday' not in df.columns:
            df['weekday'] = df['date'].dt.day_name()

        # Encode weekdays
        df['weekday'] = df['weekday'].str.lower().map(day_map)

        # Cyclic encoding for weekday
        df = self.apply_cyclic_encoding(df, 'weekday', 7)

        return df

    def create_month_features(self, df):
        """
        Create the month-related features.
        """
        # Extract day of the month and month directly from the date
        df['day_of_month'] = df['date'].dt.day
        df['month'] = df['date'].dt.month

        # Cyclic encoding for month
        df = self.apply_cyclic_encoding(df, 'month', 12)

        return df

    def create_year_feature(self, df):
        """
        Create the year feature.
        """
        if 'year' not in df.columns:
            df['year'] = df['date'].dt.year

        return df

    def create_time_features(self, df):
        """
        Create date-related features.
        """
        df = self.create_week_features(df)

        df = self.create_date_feature(df)

        df = self.create_month_features(df)

        df = self.create_year_feature(df)

        logging.info("Date features created")

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

    def fetch_historical_data(self, last_date_historical, days):
        """
        Fetch historical data from the preprocessed historical dataset based on the given date.
        """
        try:
            # Load the preprocessed historical data
            historical_data = pd.read_csv(self.historical_data_path, parse_dates=['date'])

            if historical_data.empty:
                logging.info("No historical data found, skipping historical merge")
                return pd.DataFrame()

            # Filter historical data based on the 'self.days' threshold
            start_date = last_date_historical - pd.to_timedelta(days, unit='D')
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
        first_date_current = df['date'].min()
        historical_data = self.fetch_historical_data(first_date_current, self.days)

        # Preserve current data where/if historical data overlaps
        historical_data = historical_data[historical_data['date'] < first_date_current]

        # Detect any gaps
        last_date_historical = historical_data['date'].max()
        gap_days = (first_date_current - last_date_historical).days

        # Small gaps are flagged but the merge proceeds
        if gap_days <= 5:
            logging.info(f"Proceeding with historical data merge despite {gap_days}-day gap.")
        else:
            logging.info(f"Historical data merge aborted due to a significant {gap_days}-day gap. Skipping the merge.")

            return df

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

        # Clean up the decimal spaces
        df = df.round(4)

        logging.info("Time series features created")

        return df

    def remove_historical_data(self, df, days):
        """
        Remove historical data rows older than the 'self.days' threshold from the current dataset.
        """
        # Remove historical data if it was fetched
        if self.historical_data_fetched:
            # Calculate the cutoff date based on the minimum date in the current data
            cutoff_date = df['date'].min() + pd.to_timedelta(days, unit='D')

            # Filter out rows older than the cutoff date
            df = df[df['date'] >= cutoff_date].reset_index(drop=True)

            logging.info("Historical data removed")

        return df

    def process_historical_weekly_data(self):
        """
        Re-structure a DataFrame with weekly records and create new features.
        """
        logging.info("Starting feature engineering process...")

        df = self.encode_categorical_feature(self.data, 'category')
        df = self.encode_categorical_feature(df, 'product_id')
        df = self.pivot_weekly_data(df)
        df = self.create_time_features(df)
        df = self.adjust_in_stock(df)
#         df = self.merge_historical_data(df)
        df = self.create_time_series_features(df, 'quantity')
#         df = self.remove_historical_data(df, self.days)

        return df

    def process_historical_daily_data(self):
        """
        Re-structure a DataFrame with daily records and create new features.
        """
        logging.info("Starting feature engineering process...")

        if self.data is None:
            logging.error("Empty DataFrame")
            raise ValueError("The DataFrame is empty")

        df = self.encode_categorical_feature(self.data, 'category')
        df = self.encode_categorical_feature(df, 'product_id')
        df = self.create_time_features(df)
        df = self.merge_historical_data(df)
        df = self.create_time_series_features(df, 'quantity')
        df = self.remove_historical_data(df, self.days)

        return df

    def process_prediction_data(self):
        """
        Re-structure a DataFrame with data for prediction and create new features.
        """
        logging.info("Starting feature engineering process...")

        if self.data is None:
            logging.error("Empty DataFrame")
            raise ValueError("The DataFrame is empty")

        df = self.encode_categorical_feature(self.data, 'category')
        df = self.encode_categorical_feature(df, 'product_id')
        df = self.apply_cyclic_encoding(df, 'weekday', 7)
        df = self.apply_cyclic_encoding(df, 'month', 12)
        df = self.merge_historical_data(df)
        df = self.create_time_series_features(df, 'quantity')
        df = self.remove_historical_data(df, self.days)

        return df
