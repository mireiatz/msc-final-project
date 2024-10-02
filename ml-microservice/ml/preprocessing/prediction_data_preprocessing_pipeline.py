from ml.preprocessing.prediction_data_ingestion_layer import PredictionDataIngestionLayer
from ml.preprocessing.cleaning_layer import CleaningLayer
from ml.preprocessing.general_feature_engineering_layer import GeneralFeatureEngineeringLayer
from ml.preprocessing.time_series_engineering_layer import TimeSeriesEngineeringLayer

class PredictionDataPreprocessingPipeline(PreprocessingPipeline):

    def __init__(self, data, output_path=None):
        super().__init__(output_path=output_path)
        self.data = data

    def ingest_data(self):
        return PredictionDataIngestionLayer(data=self.data).process()

    def clean_data(self, ingested_data):
        return CleaningLayer(ingested_data).process_prediction_data()

    def engineer_data(self, cleaned_data):
        # First, run the general feature engineering
        general_feature_engineering = GeneralFeatureEngineeringLayer(cleaned_data)
        engineered_data = general_feature_engineering.process_general_features()

        # Then, apply the time series feature engineering
        time_series_engineering = TimeSeriesEngineeringLayer(engineered_data, self.sales_history)
        return time_series_engineering.process_prediction_data()
