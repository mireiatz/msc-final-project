import pandas as pd
import numpy as np
import hashlib
from datetime import datetime

class CleaningLayer:

    def __init__(self, data):
        self.data = data

    def calculate_z_scores(self, df, column):
        """
        Calculate Z-scores for a given column, grouped by product_id.
        """
        def z_score_group(x):
            if len(x) > 1 and x.std(ddof=0) > 0:  # Avoid std=0 or single-entry groups
                return (x - x.mean()) / x.std(ddof=0)
            else:
                return np.nan  # Assign NaN for single-entry groups or zero std deviation

        df['z_score'] = df.groupby('product_id')[column].transform(z_score_group)

        return df

    def remove_outliers(self, df, column, z_threshold=3):
        """
        Remove outliers based on Z-scores for a given column.
        """
        df = self.calculate_z_scores(df, column)

        # Filter out rows where absolute Z-score exceeds the threshold, keeping NaNs (e.g., single-entry groups)
        df_clean = df[(np.abs(df['z_score']) <= z_threshold) | df['z_score'].isna()].copy()

        # Drop the Z-score column after filtering
        df_clean.drop(columns=['z_score'], inplace=True)

        return df_clean

    def generate_unique_id(self, name, category):
        """
        Generate a unique product ID by hashing the product name and category.
        """
        unique_string = f"{name}_{category}"

        return hashlib.md5(unique_string.encode()).hexdigest()[:8]  # Return shortened hashed

    def handle_product_ids(self, df):
        """
        Handle product IDs.
        """
        df = df.rename(columns={'product_id': 'original_product_id'})

        df['product_id'] =  df.apply(lambda row: self.generate_unique_id(row['product_name'], row['category']), axis=1)

        return df

    def clean_sales_columns(self, df):
        """
        Clean sales columns 'monday-sunday'.
        """
        sales_columns = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']

        df[sales_columns] = df[sales_columns].fillna(0)  # Fill NaNs with 0
        df[sales_columns] = df[sales_columns].abs().astype(int)  # Convert all values to absolute integers

        return df

    def clean_quantity_column(self, df):
        """
        Clean 'quantity' column.
        """
        sales_columns = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']

        df['quantity'] = df[sales_columns].sum(axis=1)  # Replace quantity values with the sum of sales across all days

        return df

    def clean_value_column(self, df):
        """
        Clean 'value' column.
        """
        df['value'] = df['value'].apply(lambda x: abs(x) if x < 0 else x)  # Ensure positive values

        return df

    def standardise_in_stock(self, df):
        """
        Standardise 'in_stock' column values to binary format (0 or 1).
        """
        df['in_stock'] = df['in_stock'].apply(lambda x: 1 if x > 0 else 0)  # Assign 0 for below 0 values, 1 for greater than 1 values

        return df

    def standardise_category_column(self, df):
        """
        Standardise string values in a column.
        """
        # Clean the labels
        df['category'] = df['category'].str.lower()  # Lowercase everything
        df['category'] = df['category'].str.replace(r'[^\w\s]', ' ', regex=True)  # Remove special characters
        df['category'] = df['category'].str.replace(r'\s+', '_', regex=True)  # Replace spaces with underscores

        # Perform specific replacements
        df['category'] = df['category'].replace({
            'sauces_pickle': 'sauces_pickles',
            'washing_powder': 'washing_powders',
            'petfood': 'pet_food',
            'lower_rate': 'miscellaneous',
            'standard_rate': 'miscellaneous',
            'zero_rate': 'miscellaneous',
            '': 'miscellaneous'
        })

        return df

    def insert_missing_product_weeks(self, df):
        """
        Ensure all products have sales records for every week, i.e. insert weekly no-sales data.
        """
        # Get all unique products and all unique weeks
        all_products = df[['product_id', 'product_name', 'category']].drop_duplicates()
        all_weeks = df[['year', 'week']].drop_duplicates()

        # Create a cross join between all_products and all_weeks to get all combinations of products and weeks
        all_combinations = all_products.assign(key=1).merge(all_weeks.assign(key=1), on='key').drop('key', axis=1)

        # Left join with the original dataframe to find missing products for each week
        merged_df = pd.merge(all_combinations, df, on=['product_id', 'year', 'week'], how='left', suffixes=('', '_orig'))

        # Identify rows that were newly inserted (those with NaNs in the sales columns)
        new_rows = merged_df[merged_df['monday'].isna()]

        # Process new rows
        if not new_rows.empty:
            sales_columns = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'quantity']

            # Fill sales columns with 0 integer values
            for col in sales_columns:
                merged_df.loc[new_rows.index, col] = 0
                merged_df[col] = merged_df[col].astype(int)

            # Fill 'value' with 0 float values
            merged_df.loc[new_rows.index, 'value'] = 0.0

            # Sort the dataframe and apply forward fill to 'in_stock'
            merged_df = merged_df.sort_values(by=['product_id', 'year', 'week'])
            merged_df['in_stock'] = merged_df.groupby('product_id')['in_stock'].ffill().fillna(0).astype(int)

        # Return the filled dataframe without unnecessary duplicate columns
        return merged_df.drop(columns=[col for col in merged_df.columns if col.endswith('_orig')])

    def drop_duplicates(self, df, columns):
        """
        Drop duplicates based on product IDs, names, dates and sales values.
        """

        return df.drop_duplicates(subset=columns, keep='first')

    def process(self):
        """
        Apply the full data cleaning process to the DataFrame:
        - Drop duplicates.
        - Remove outliers in 'quantity'.
        - Clean sales, 'quantity' and 'value' columns.
        - Handle missing product IDs.
        - Standardise 'category' and 'in_stock' columns.
        - Fill rows for missing weekly data.
        """
        print(f"Remove outliers at {datetime.now()}")
        df = self.remove_outliers(self.data, 'quantity')
        print(f"Clean sales at {datetime.now()}")
        df = self.clean_sales_columns(df)
        print(f"Clean quantity at {datetime.now()}")
        df = self.clean_quantity_column(df)
        print(f"Clean values at {datetime.now()}")
        df = self.clean_value_column(df)
        print(f"Standardise categories at {datetime.now()}")
        df = self.standardise_category_column(df)
        print(f"Drop duplicate products at {datetime.now()}")
        df = self.drop_duplicates(df, ['product_name', 'category', 'year', 'week'])
        print(f"Handle product ids at {datetime.now()}")
        df = self.handle_product_ids(df)
        print(f"Standardise in stock at {datetime.now()}")
        df = self.standardise_in_stock(df)
        print(f"Fill missing products at {datetime.now()}")
        df = self.insert_missing_product_weeks(df)

        return df
