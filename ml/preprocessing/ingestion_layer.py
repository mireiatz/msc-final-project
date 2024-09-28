import os
import pandas as pd
import logging

class IngestionLayer:

    def __init__(self, data_path, output_path):
        self.data_path = data_path
        self.output_path = output_path

    def load_and_combine_files(self):
        """
        Load and combine all CSV files from a directory into a single DataFrame.
        """
        # Create directory for output if it doesn't exist
        os.makedirs(os.path.dirname(self.output_path), exist_ok=True)

        # Initialise an empty list to store DataFrames
        combined_data = []

        # Iterate through each file in the data path directory
        for filename in os.listdir(self.data_path):
            file_path = os.path.join(self.data_path, filename)

            # Ensure the file is a non-empty CSV
            if filename.endswith('.csv') and os.path.getsize(file_path) > 0:
                try:
                    df = pd.read_csv(file_path, dtype={'product_id': str, 'year': int, 'week': int})
                    combined_data.append(df)
                except pd.errors.EmptyDataError:
                    logging.error(f"Error: File {filename} is empty or corrupted. Skipping.")
                except Exception as e:
                   logging.error(f"Error reading {filename}: {e}")
            else:
                logging.info(f"Skipping non-CSV or empty file: {filename}")

        # Check if there is any valid data
        if not combined_data:
            logging.error("Error combining DataFrames.")
            raise IOError(f"Error combining DataFrames: {e}")

        # Concatenate all DataFrames in the list
        try:
            df = pd.concat(combined_data, ignore_index=True)
        except Exception as e:
            logging.error("Error combining DataFrames.")
            raise ValueError(f"Error combining DataFrames: {e}")

        return df

    def save_data(self, df):
        """
        Save the combined DataFrame to a CSV file.
        """
        try:
            # Create the output directory if it doesn't exist
            os.makedirs(os.path.dirname(self.output_path), exist_ok=True)

            # Save DataFrame to CSV
            df.to_csv(self.output_path, index=False)
            logging.info(f"Data successfully saved to {self.output_path}")
        except Exception as e:
            logging.error(f"Error saving the data to {self.output_path}: {e}")
            raise IOError(f"Error saving the data to {self.output_path}: {e}")

    def process(self):
        """
        Load, combine, and cache data from CSV file(s), or load from cache if available.
        """
        logging.info("Starting the ingestion process...")

        try:
            # If cached data is available, read and return it
            if os.path.exists(self.output_path):
                logging.info(f"Loading cached data from {self.output_path}")
                return pd.read_csv(self.output_path, dtype={'product_id': 'str'})

            # If 'data_path' is a directory, process multiple files
            if os.path.isdir(self.data_path):
                df = self.load_and_combine_files()
            else:
                # Load single CSV file
                df = pd.read_csv(self.data_path, dtype={'product_id': str})

            # Save the combined or single CSV data
            self.save_data(df)
            return df

        except FileNotFoundError as e:
            logging.error(f"Data path not found: {self.data_path}, Error: {e}")
            raise FileNotFoundError(f"Data path not found: {self.data_path}, Error: {e}")
        except pd.errors.EmptyDataError:
            logging.error(f"The CSV file at {self.data_path} is empty.")
            raise pd.errors.EmptyDataError(f"The CSV file at {self.data_path} is empty.")
        except Exception as e:
            logging.error(f"Error during data ingestion process: {e}")
            raise RuntimeError(f"Error during data ingestion process: {e}")