from preprocessing.ingestion_layer import IngestionLayer
from preprocessing.cleaning_layer import CleaningLayer
from preprocessing.feature_engineering_layer import FeatureEngineeringLayer
import time
from datetime import datetime

class PreprocessingPipeline:

    def __init__(self, data_dir, output_path):
        self.data_dir = data_dir
        self.output_path = output_path

    def run(self, historical_data=None):
        print(f"Pipeline started at {datetime.now()}")
        start_time = time.time()

        # Step 1: Ingestion - Read raw data from the directory and save to 'raw_data.csv'
        print("Starting Ingestion...")
        ingestion = IngestionLayer(self.data_dir, self.output_path + '/raw_data.csv')
        ingested_data = ingestion.process()

        # Step 2: Cleaning - Clean the ingested data
        print("Starting Cleaning...")
        cleaning = CleaningLayer(ingested_data)
        cleaned_data = cleaning.process()

        # Step 3: Feature Engineering - Re-structure the dataset and create additional features
        print("Starting Feature Engineering...")
        feature_engineering = FeatureEngineeringLayer(cleaned_data)
        structured_data = feature_engineering.process(historical_data)

        # Step 4: Save data
        print("Starting data saving...")
        structured_data.to_csv(output_path + '/processed_data.csv', index=False)

        total_time = time.time() - start_time
        print(f"Data pipeline finished processing in {total_time:.2f} seconds.")
