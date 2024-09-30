
from ml.preprocessing.ingestion_layer import IngestionLayer
from ml.preprocessing.cleaning_layer import CleaningLayer
from ml.preprocessing.feature_engineering_layer import FeatureEngineeringLayer
from ml.config import config
import logging
import os
import shutil
from datetime import datetime

class PreprocessingPipeline:

    def __init__(self, data_path=None, output_path=None):
        self.data_path = data_path or config.HISTORICAL_DATA_RAW
        self.output_path = output_path or config.HISTORICAL_DATA_PROCESSED

    def run(self):
        # Step 1: Ingestion
        ingested_data = IngestionLayer(self.data_path).process()

        # Step 2: Call the specific cleaning and feature engineering processes
        cleaned_data = self.clean_data(ingested_data)
        engineered_data = self.engineer_features(cleaned_data)

        # Step 3: Save the preprocessed data after making a backup
        # Create the filename using a timestamp and set paths
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")

        # Assuming you want the backup directory at the same level as the processed data directory
        backup_dir = os.path.join(os.path.dirname(self.output_path), 'backup')

        # Ensure the paths for the backup and main files are set correctly
        backup_file_path = os.path.join(backup_dir, f'processed_data_{timestamp}.csv')
        main_file_path = os.path.join(self.output_path, 'processed_data.csv')

        # Create directories if they don't exist
        os.makedirs(self.output_path, exist_ok=True)
        os.makedirs(backup_dir, exist_ok=True)

        # Save a main and backup files
        try:
            engineered_data.to_csv(main_file_path, index=False)
            engineered_data.to_csv(backup_file_path, index=False)

            logging.info(f"Backup created at {backup_file_path}")
        except Exception as e:
            logging.error(f"Failed to create backup: {e}")
            return

        logging.info("Preprocessing pipeline completed")

        return engineered_data

    def clean_data(self, ingested_data):
        raise NotImplementedError("Subclass must implement 'clean_data'")

    def engineer_features(self, cleaned_data):
        raise NotImplementedError("Subclass must implement 'engineer_features'")
