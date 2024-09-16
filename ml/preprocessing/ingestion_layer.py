import os
import pandas as pd
import logging

class IngestionLayer:

    def __init__(self, data_dir, output_path):
        self.data_dir = data_dir
        self.output_path = output_path

    def load_and_combine_weekly_files(self):
        """Load and combine all CSV files from the directory into a single DataFrame."""
        if not os.path.exists(self.data_dir):
            raise FileNotFoundError(f"The specified data directory '{self.data_dir}' does not exist.")

        # Initialize an empty list to store DataFrames
        combined_data = []

        for filename in os.listdir(self.data_dir):
            file_path = os.path.join(self.data_dir, filename)
            if filename.endswith('.csv') and os.path.getsize(file_path) > 0:
                df = pd.read_csv(file_path, dtype={'product_id': str})
                combined_data.append(df)
            else:
                logging.warning(f"Skipping empty file: {filename}")

        # Concatenate all DataFrames
        combined_df = pd.concat(combined_data, ignore_index=True)

        if combined_df.empty:
            raise ValueError("No valid data was found in the provided directory.")

        return combined_df

    def save_data(self, df):
        """Save the combined DataFrame to a CSV file."""
        df.to_csv(self.output_path, index=False)

    def process(self):
        """Load, combine, and cache data from CSV files, or load from cache if available."""
        # If cached data is available, read and return it
        if os.path.exists(self.output_path):
            logging.info(f"Loading cached data from {self.output_path}")
            return pd.read_csv(self.output_path, dtype={'product_id': str})

        logging.info("No cache found, loading and combining data files.")
        # Load and combine data if no cached file is available
        combined_df = self.load_and_combine_weekly_files()
        self.save_data(combined_df)
        return combined_df
