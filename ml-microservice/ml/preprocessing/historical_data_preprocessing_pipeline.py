from ml.preprocessing.preprocessing_pipeline import PreprocessingPipeline
from ml.preprocessing.file_ingestion_layer import FileIngestionLayer
from ml.preprocessing.cleaning_layer import CleaningLayer
from ml.preprocessing.feature_engineering_layer import FeatureEngineeringLayer
from ml.preprocessing.time_series_engineering_layer import TimeSeriesEngineeringLayer
from ml.config import config

class HistoricalDataPreprocessingPipeline(PreprocessingPipeline):

    def __init__(self, data_path=None, output_path=None, data_type='daily'):
        super().__init__(
            data_path or config.HISTORICAL_DATA_RAW,
            output_path or config.HISTORICAL_DATA_PROCESSED
        )
        self.data_type = data_type

    def ingest_data(self):
        return FileIngestionLayer(data_path=self.data_path).process()

    def clean_data(self, ingested_data):
        cleaning = CleaningLayer(ingested_data)

        if self.data_type == 'daily':
            return cleaning.process_historical_daily_data()
        elif self.data_type == 'weekly':
            return cleaning.process_historical_weekly_data()

    def engineer_features(self, cleaned_data):
        feature_engineering = FeatureEngineeringLayer(cleaned_data)

        if self.data_type == 'daily':
            return feature_engineering.process_historical_daily_data()
        elif self.data_type == 'weekly':
            return feature_engineering.process_historical_weekly_data()

    def engineer_time_series(self, engineered_data):
        return TimeSeriesEngineeringLayer(engineered_data).process_historical_data()

    def handle_data(self, data):
        """
        Save data into a file, creating a backup.
        """
        logging.info(f"Saving historical data...")

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