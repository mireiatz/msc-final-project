from ml.config import config
import logging
import os
from datetime import datetime

class PreprocessingPipeline:

    def __init__(self, data_path=None, output_path=None):
        self.data_path = data_path
        self.output_path = output_path

    def run(self):
        logging.info("Starting preprocessing pipeline...")

        # Step 1: Ingest the data
        ingested_data = self.ingest_data()

        # Step 2: Call the specific cleaning and feature engineering processes
        cleaned_data = self.clean_data(ingested_data)
        structured_data = self.engineer_data(cleaned_data)

        # Step 3: Save the preprocessed data after making a backup
        self.save_data(structured_data)

        logging.info("Preprocessing pipeline completed")

    def ingest_data(self):
        raise NotImplementedError("Subclass must implement 'ingest_data'")

    def clean_data(self, ingested_data):
        raise NotImplementedError("Subclass must implement 'clean_data'")

    def engineer_data(self, cleaned_data):
        raise NotImplementedError("Subclass must implement 'engineer_features'")

    def save_data(self, data):
        logging.info(f"Saving data...")

        # Define paths for the backup and main files
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        backup_dir = config.HISTORICAL_DATA_BACKUP
        backup_file_path = os.path.join(backup_dir, f'processed_data_{timestamp}.csv')
        main_file_path = os.path.join(self.output_path, 'processed_data.csv')

        # Create directories if they don't exist
        try:
            os.makedirs(self.output_path, exist_ok=True)
            os.makedirs(backup_dir, exist_ok=True)
        except Exception as e:
            logging.error(f"Error creating directories: {e}")
            return

        # Save a main and backup files
        try:
            data.to_csv(main_file_path, index=False)
            data.to_csv(backup_file_path, index=False)

            logging.info(f"Data saved at {main_file_path} (backup at: {backup_file_path})")
        except Exception as e:
            logging.error(f"Error saving data at {main_file_path} (backup at: {backup_file_path}) | Error: {e}")
            return
