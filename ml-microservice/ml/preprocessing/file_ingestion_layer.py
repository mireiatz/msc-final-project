import logging
import os
import pandas as pd

class FileIngestionLayer:

    def __init__(self, data_path):
        self.data_path = data_path

    def read_file(self, file_path):
        """
        Read a single CSV file into a DataFrame.
        """
        try:
            df = pd.read_csv(file_path, dtype={'product_id': str})

            return df
        except pd.errors.EmptyDataError:
            logging.error(f"Error: File {file_path} is empty or corrupted.")
            raise pd.errors.EmptyDataError(f"File {file_path} is empty or corrupted.")
        except Exception as e:
            logging.error(f"Error reading file {file_path}: {e}")
            raise IOError(f"Error reading file {file_path}: {e}")


    def combine_files(self, files):
        """
        Combine multiple CSV files into a single DataFrame.
        """
        combined_data = []
        for file_path in files:
            try:
                df = self.read_file(file_path)
                combined_data.append(df)
            except Exception as e:
                logging.error(f"Skipping file {file_path} due to error: {e}")

        # Ensure we have data to combine
        if not combined_data:
            logging.error("No valid data from the files to combine.")
            raise IOError("No valid data from the files to combine.")

        # Concatenate the DataFrames
        try:
            df = pd.concat(combined_data, ignore_index=True)
            return df
        except Exception as e:
            logging.error(f"Error combining DataFrames: {e}")
            raise ValueError(f"Error combining DataFrames: {e}")

    def load_files(self):
        """
        Load all valid CSV files from a directory or read a single file if it's not a directory.
        """
        logging.info(f"Checking full path of directory: {self.data_path}")

        # If the path is a directory, process files in the directory
        if os.path.isdir(self.data_path):
            logging.info(f"Files in directory: {os.listdir(self.data_path)}")

            # Get list of all valid CSV files in the directory
            files = [os.path.join(self.data_path, f) for f in os.listdir(self.data_path)
                     if f.endswith('.csv') and os.path.getsize(os.path.join(self.data_path, f)) > 0]

            # Handle no valid CSV files
            if not files:
                logging.error("No valid CSV files found in the directory.")
                raise IOError("No valid CSV files found in the directory.")

            # If there's only one file, return that DataFrame
            if len(files) == 1:
                return self.read_file(files[0])

            # If there are multiple files, combine them
            return self.combine_files(files)

        # If it's a single file, read it directly
        elif os.path.isfile(self.data_path):  # Check if it's a valid file
            return self.read_file(self.data_path)

        else:
            logging.error(f"The provided path {self.data_path} is neither a file nor a directory.")
            raise FileNotFoundError(f"The provided path {self.data_path} is neither a file nor a directory.")

    def process(self):
        """
        Main method to load data (single file or directory) and return the DataFrame.
        """
        logging.info("Starting the ingestion process...")

        try:
            df = self.load_files()

            logging.info("Data ingested")

            return df

        except FileNotFoundError as e:
            logging.error(f"Data path not found: {self.data_path}, Error: {e}")
            raise FileNotFoundError(f"Data path not found: {self.data_path}, Error: {e}")
        except pd.errors.EmptyDataError as e:
            logging.error(f"The CSV file at {self.data_path} is empty.")
            raise pd.errors.EmptyDataError(f"The CSV file at {self.data_path} is empty.")
        except Exception as e:
            logging.error(f"Error during data ingestion process: {e}")
            raise RuntimeError(f"Error during data ingestion process: {e}")
