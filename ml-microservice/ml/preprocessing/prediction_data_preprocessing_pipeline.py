from ml.preprocessing.preprocessing_pipeline import PreprocessingPipeline

from ml.preprocessing.ingestion_layer import IngestionLayer
from ml.preprocessing.cleaning_layer import CleaningLayer
from ml.preprocessing.feature_engineering_layer import FeatureEngineeringLayer

class PredictionDataPreprocessingPipeline(PreprocessingPipeline):

    def __init__(self, data_path, output_path):
        super().__init__(data_path, output_path)

    def clean_data(self, ingested_data):
        return CleaningLayer(ingested_data).process_prediction_data()

    def engineer_features(self, cleaned_data):
        return FeatureEngineeringLayer(cleaned_data).process_prediction_data()
