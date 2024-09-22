import os
import pandas as pd

class IngestionLayer:

    def __init__(self, data_path, output_path):
        self.data_path = data_path
        self.output_path = output_path

    def load_and_combine_files(self):
        """
        Load and combine all CSV files from a directory into a single DataFrame.
        """
        # If the directory doesn't exist, create it
        os.makedirs(os.path.dirname(self.output_path), exist_ok=True)

        # Initialise an empty list to store DataFrames
        combined_data = []

        # For each file in the directory, add the data into a combined DataFrame
        for filename in os.listdir(self.data_path):
            file_path = os.path.join(self.data_path, filename)

            if filename.endswith('.csv') and os.path.getsize(file_path) > 0:
                df = pd.read_csv(file_path, dtype={'product_id': str})
                combined_data.append(df)
            else:
                print(f"Skipping empty file: {filename}")

        # Concatenate all DataFrames
        df = pd.concat(combined_data, ignore_index=True)

        if df.empty:
            raise ValueError("No valid data was found in the provided directory.")

        return df

    def save_data(self, df):
        """
        Save the combined DataFrame to a CSV file.
        """
        os.makedirs(os.path.dirname(self.output_path), exist_ok=True)  # If the directory doesn't exist, create it
        df.to_csv(self.output_path, index=False)

    def process(self):
        """
        Load, combine, and cache data from CSV file(s), or load from cache if available.
        """
        # If cached data is available, read and return it
        if os.path.exists(self.output_path):
            return pd.read_csv(self.output_path, dtype={'product_id': 'str'})

        # If 'data_path' is a directory, process multiple files, otherwise load a single file
        if os.path.isdir(self.data_path):
            df = self.load_and_combine_files()
        else:
            df = pd.read_csv(self.data_path, dtype={'product_id': str})

        # Save the data
        self.save_data(df)

        return df