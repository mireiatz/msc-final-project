from preprocessing.ingestion_layer import IngestionLayer
from preprocessing.cleaning_layer import CleaningLayer
from preprocessing.feature_engineering_layer import FeatureEngineeringLayer
import time
from datetime import datetime

class PreprocessingPipeline:

    def __init__(self, data_dir, output_path):
        self.data_dir = data_dir
        self.output_path = output_path

    def run(self):
        print(f"Pipeline started at {datetime.now()}")
        start_time = time.time()

        # Step 1: Ingestion - Read raw data from the directory and save to raw_data.csv
        print("Starting Ingestion...")
        ingestion_start = time.time()
        ingestion = IngestionLayer(self.data_dir, self.output_path + '/raw_data.csv')
        ingested_data = ingestion.process()
        ingestion_end = time.time()
        print(f"Ingestion completed in {ingestion_end - ingestion_start:.2f} seconds.")

        # Step 2: Cleaning - Filter or clean the ingested data
        print("Starting Cleaning...")
        cleaning_start = time.time()
        cleaning = CleaningLayer(ingested_data)
        cleaned_data = cleaning.process()
        cleaning_end = time.time()
        print(f"Cleaning completed in {cleaning_end - cleaning_start:.2f} seconds.")

        # Step 3: Feature Engineering - Create additional features and save processed data
        print("Starting Feature Engineering...")
        feature_engineering_start = time.time()
        feature_engineering = FeatureEngineeringLayer(cleaned_data, self.output_path + '/processed_data.csv')
        structured_data = feature_engineering.process()
        feature_engineering_end = time.time()
        print(f"Feature Engineering completed in {feature_engineering_end - feature_engineering_start:.2f} seconds.")

        total_time = time.time() - start_time
        print(f"Data pipeline finished processing in {total_time:.2f} seconds.")
