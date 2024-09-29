from ml.preprocessing.ingestion_layer import IngestionLayer
from ml.preprocessing.cleaning_layer import CleaningLayer
from ml.preprocessing.feature_engineering_layer import FeatureEngineeringLayer
from datetime import datetime
import logging
import time

class PreprocessingPipeline:

    def __init__(self, data_path, output_path, data_type):
        self.data_path = data_path
        self.output_path = output_path
        self.data_type = data_type

    def run(self):
        """
        Ingest data and process it accordingly depending on whether it's weekly or daily data.
        """
        # Step 1: Ingestion - Read raw data from a directory/file and save to 'raw_data.csv'
        ingestion = IngestionLayer(self.data_path, self.output_path + '/raw_data.csv')
        ingested_data = ingestion.process()

        # Step 2: Process according to the type of data
        if self.data_type == 'weekly':
            structured_data = self.process_weekly_data(ingested_data)
        elif self.data_type == 'daily':
            structured_data = self.process_daily_data(ingested_data)
        else:
            raise ValueError("Invalid data type. Use 'weekly' or 'daily'.")

        # Step 3: Save data
        logging.info("Saving preprocessed data...")
        structured_data.to_csv(self.output_path + '/processed_data.csv', index=False)

        logging.info(f"Data pipeline finished processing")

        return structured_data

    def process_weekly_data(self, ingested_data):
        # Step 2.1: Cleaning - Clean the ingested weekly data
        cleaning = CleaningLayer(ingested_data)
        cleaned_data = cleaning.process_weekly_data()

        # Step 2.2: Feature Engineering - Re-structure the dataset and create additional features
        feature_engineering = FeatureEngineeringLayer(cleaned_data)
        structured_data = feature_engineering.process_weekly_data()

        return structured_data

    def process_daily_data(self, ingested_data):
        # Step 2.1: Cleaning - Clean the ingested daily data
        cleaning = CleaningLayer(ingested_data)
        cleaned_data = cleaning.process_daily_data()

        # Step 2.2: Feature Engineering - Re-structure the dataset and create additional features
        feature_engineering = FeatureEngineeringLayer(cleaned_data)
        structured_data = feature_engineering.process_daily_data()

        return structured_data