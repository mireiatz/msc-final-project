from ml.modeling.evaluator import Evaluator
from ml.modeling.predictor import Predictor
from ml.preprocessing.historical_data_preprocessing_pipeline import HistoricalDataPreprocessingPipeline
from ml.config import config
import logging
import pandas as pd

def main(model_path, X_test, y_test):

    try:
        # Run predictions
        predictor = Predictor(model_path=model_path)
        predictions = predictor.run_predictions_for_evaluation(X_test)

        # Evaluate the predictions
        evaluator = Evaluator()
        metrics = evaluator.run(y_test, predictions)

        logging.info(f"Evaluation completed for model {model_path}. Metrics below.")
        logging.info(metrics)
    except Exception as e:
        logging.error(f"An error occurred during model evaluation: {str(e)}")

if __name__ == "__main__":
    logging.info("Starting model evaluation script...")

    parser = argparse.ArgumentParser(description='Evaluate a trained model')
    parser.add_argument('--model_path', required=False, help='Path to the trained model file')
    parser.add_argument('--X_test', required=False, help='Path to test features')
    parser.add_argument('--y_test', required=False, help='Path to test labels')

    try:
        X_test = pd.read_csv(args.X_test)
        y_test = pd.read_csv(args.y_test)
        main(args.model_path, X_test, y_test)
    except Exception as e:
        logging.error(f"An error occurred while loading test data: {str(e)}")
