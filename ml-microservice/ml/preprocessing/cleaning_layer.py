import logging
import pandas as pd
import numpy as np
import hashlib
from datetime import datetime, timedelta

class CleaningLayer:

    def __init__(self, data):
        self.data = data

    def validate_columns(self, df, required_columns):
        """
        Check that all required columns are present in the DataFrame.
        """
        missing_columns = [col for col in required_columns if col not in df.columns]
        if missing_columns:
            logging.error(f"Missing required columns: {missing_columns}")
            raise KeyError(f"Missing required columns: {missing_columns}")

        if 'week' in required_columns and 'year' in required_columns:
            # Drop rows with NaN values in the 'year' and 'week' columns
            df = df.dropna(subset=['year', 'week'])

            # Cast 'year' and 'week' columns to integers
            df.loc[:, 'year'] = df['year'].astype(int)
            df.loc[:, 'week'] = df['week'].astype(int)

        return df

    def standardise_category_column(self, df):
        """
        Standardise the 'category' column.
        """
        # Clean the labels
        df.loc[:, 'category'] = df['category'].str.lower()  # Convert to lowercase
        df.loc[:, 'category'] = df['category'].str.replace(r'[^\w\s]', ' ', regex=True)  # Remove special characters
        df.loc[:, 'category'] = df['category'].str.replace(r'\s+', '_', regex=True)  # Replace spaces with underscores

        # Apply specific replacements for consistency
        df.loc[:, 'category'] = df['category'].replace({
            'sauces_pickle': 'sauces_pickles',
            'washing_powder': 'washing_powders',
            'petfood': 'pet_food',
            'lower_rate': 'miscellaneous',
            'standard_rate': 'miscellaneous',
            'zero_rate': 'miscellaneous',
            '': 'miscellaneous'
        })
        logging.info("'category' column standardised")

        return df

    def generate_unique_id(self, name, category):
        """
        Generate a unique product ID by hashing the product name and category.
        """
        unique_string = f"{name}_{category}"

        return hashlib.md5(unique_string.encode()).hexdigest()[:8]  # Return an 8-character hash

    def handle_product_ids(self, df):
        """
        Create unique product IDs based on product name and category, and keep the originals.
        """
        # Keep the original product IDs by transferring them to a new column
        df = df.rename(columns={'product_id': 'original_product_id'})

        # Generate new unique IDs in the 'product_id' column
        df['product_id'] = df.apply(lambda row: self.generate_unique_id(row['product_name'], row['category']), axis=1)

        logging.info("Product IDs handled")

        return df

    def drop_duplicates(self, df, columns=['product_id', 'year', 'week']):
        """
        Drop duplicates based on specified columns.
        """
        original_len = len(df)  # To check number of duplicates
        df = df.drop_duplicates(subset=columns, keep='first')

        logging.info(f"Dropped {original_len - len(df)} duplicate rows")

        return df

    def calculate_cutoff_date(self, year, week, cutoff_period_weeks):
        """
        Calculate the cutoff year and week based on the current year/week and the cutoff period in weeks.
        """
        # Create a datetime object for the first day of the given week
        current_date = f"{year}-W{week}-1"
        current_date = pd.to_datetime(current_date, format="%Y-W%U-%w")

        # Subtract the cutoff period in weeks
        cutoff_date = current_date - timedelta(weeks=cutoff_period_weeks)

        # Convert cutoff date back to year and week format
        cutoff_year = cutoff_date.year
        cutoff_week = cutoff_date.isocalendar()[1]  # Week number

        return cutoff_year, cutoff_week

    def remove_inactive_products(self, df, cutoff_period_weeks=12):
        """
        Remove products that haven't had sales in the last `cutoff_period_weeks`.
        """
        # Find the most recent year and week in the dataset
        max_year, max_week = df[['year', 'week']].max()

        # Calculate the cutoff date
        cutoff_year, cutoff_week = self.calculate_cutoff_date(int(max_year), int(max_week), cutoff_period_weeks)

        # Identify products that had activity after the cutoff
        last_appearance = df.groupby('product_id').agg({'year': 'max', 'week': 'max'})

        # Filter active products based on their last appearance
        active_products = last_appearance[
            (last_appearance['year'] > cutoff_year) |
            ((last_appearance['year'] == cutoff_year) & (last_appearance['week'] >= cutoff_week))
        ].index

        # Keep only active products
        total_products_before = df['product_id'].nunique()  # Count products for logging
        df = df[df['product_id'].isin(active_products)]

        # Calculate the number of inactive products removed
        total_products_after = df['product_id'].nunique()
        removed_products = total_products_before - total_products_after
        logging.info(f"Removed {removed_products} inactive products (no sales in the last {cutoff_period_weeks} weeks)")

        return df

    def clean_sales_columns(self, df, columns=['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']):
        """
        Clean sales columns ('monday' to 'sunday') by ensuring positive integers.
        """
        # Ensure 'columns' is a list even if a single column is passed
        if isinstance(columns, str):
            columns = [columns]

        # Convert to numeric, forcing errors to NaN
        df[columns] = df[columns].apply(pd.to_numeric, errors='coerce')

        # Convert to positive integers, filling NaNs with 0
        df[columns] = df[columns].fillna(0).abs().astype(int)

        logging.info("Sales columns cleaned")

        return df

    def clean_quantity_column(self, df):
        """
        Clean the 'quantity' column by summing across all sales columns (monday to sunday).
        """
        sales_columns = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']

        # Sum sales columns
        df['quantity'] = df[sales_columns].sum(axis=1)

        logging.info("'quantity' column cleaned")

        return df

    def clean_price_column(self, df, column='value'):
        """
        Clean the price indicating column by ensuring positive decimals.
        """
        # Coerce to numeric, forcing errors to NaN
        df[column] = pd.to_numeric(df[column], errors='coerce')

        # Get the absolute value, filling NaNs with 0
        df[column] = np.abs(df[column].fillna(0))

        logging.info("Price indicating column cleaned")

        return df

    def standardise_in_stock(self, df, is_daily=False):
        """
        Standardise the 'in_stock' column values to binary format (0 or 1).
        """
        # Coerce the 'in_stock' column to numeric, forcing errors to NaN
        df['in_stock'] = pd.to_numeric(df['in_stock'], errors='coerce')

        # Convert > 0 values to 1 and everything else to 0 (including NaNs)
        df['in_stock'] = np.where(df['in_stock'] > 0, 1, 0)

        if is_daily:
            df.loc[df['quantity'] > 0, 'in_stock'] = 1

        logging.info("'in_stock' column standardised")

        return df

    def insert_missing_product_weekly(self, df):
        """
        Ensure all products have sales records for every week by inserting no-sales data for missing weeks.
        """
        # Get all unique products and all unique weeks
        all_products = df[['product_id', 'product_name', 'category']].drop_duplicates()
        all_weeks = df[['year', 'week']].drop_duplicates()

        # Create a cross join between all_products and all_weeks to get all combinations
        all_combinations = all_products.merge(all_weeks, how='cross')

        # Left join with the original DataFrame to find missing products for each week
        merged_df = pd.merge(all_combinations, df, on=['product_id', 'year', 'week'], how='left', suffixes=('', '_orig'))

        # Fill missing sales columns and 'value' in a vectorised manner
        sales_columns = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'quantity']
        merged_df[sales_columns] = merged_df[sales_columns].fillna(0).astype(int)
        merged_df['value'] = merged_df['value'].fillna(0.0)

        # Sort the DataFrame and apply forward fill to 'in_stock'
        merged_df = merged_df.sort_values(by=['product_id', 'year', 'week'])
        merged_df['in_stock'] = merged_df.groupby('product_id')['in_stock'].ffill().fillna(0).astype(int)

        # Return the filled DataFrame without unnecessary duplicate columns
        df = merged_df.drop(columns=[col for col in merged_df.columns if col.endswith('_orig')])

        logging.info("Missing weeks inserted")

        return df


    def process_historical_weekly_data(self):
        """
        Apply the full data cleaning process to a DataFrame with weekly sales.
        """
        logging.info("Starting the data cleaning process...")

        required_columns = ['product_id', 'product_name', 'category', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'value', 'in_stock', 'year', 'week']

        try:
            df = self.validate_columns(self.data, required_columns)
        except KeyError as e:
            logging.error(f"Missing columns in the data: {e}")
            raise KeyError(f"Missing columns in the data: {e}")

        df = self.standardise_category_column(df)
        df = self.handle_product_ids(df)
        df = self.drop_duplicates(df)
        df = self.remove_inactive_products(df)
        df = self.clean_sales_columns(df)
        df = self.clean_quantity_column(df)
        df = self.clean_price_column(df)
        df = self.standardise_in_stock(df)
        df = self.insert_missing_product_weekly(df)

        return df

    def process_historical_daily_data(self):
        """
        Apply the full data cleaning process to a DataFrame with daily sales.
        """
        logging.info("Starting the data cleaning process...")

        if self.data is None:
            logging.error("Empty DataFrame")
            raise ValueError("The DataFrame is empty")

        required_columns = ['product_id', 'product_name', 'category', 'quantity', 'per_item_value', 'in_stock', 'date']

        try:
           df = self.validate_columns(self.data, required_columns)
        except KeyError as e:
            logging.error(f"Missing columns in the data: {e}")
            raise KeyError(f"Missing columns in the data: {e}")

        df = self.standardise_category_column(df)
        df = self.handle_product_ids(df)
        df = self.drop_duplicates(df, ['product_id', 'date'])
        df = self.clean_sales_columns(df, ['quantity'])
        df = self.clean_price_column(df, 'per_item_value')
        df = self.standardise_in_stock(df, True)

        return df

    def process_prediction_data(self):
        """
        Apply a lighter data cleaning process to a DataFrame for prediction.
        """
        logging.info("Starting the data cleaning process...")

        if self.data is None:
            logging.error("Empty DataFrame")
            raise ValueError("The DataFrame is empty")

        required_columns = ['product_id', 'product_name', 'category', 'per_item_value', 'in_stock', 'date']

        try:
            df = self.validate_columns(self.data, required_columns)
        except KeyError as e:
            logging.error(f"Missing columns in the data: {e}")
            raise KeyError(f"Missing columns in the data: {e}")

        df = self.standardise_category_column(df)
        df = self.handle_product_ids(df)

        return df