import unittest
from unittest.mock import patch, MagicMock
import pandas as pd
from ml.scripts.evaluate_model import main as evaluate_main

class TestEvaluateModel(unittest.TestCase):

    @patch('ml.modeling.predictor.Predictor.run_predictions_for_evaluation')
    @patch('ml.modeling.evaluator.Evaluator.run')
    def test_main_success(self, mock_evaluator_run, mock_predictor_run):
        """
        Test successful prediction and evaluation triggering.
        """
        # Mock predictions and evaluation
        mock_predictor_run.return_value = pd.Series([100, 150, 200])
        mock_evaluator_run.return_value = {'mae': 10, 'rmse': 12, 'r2': 0.9}

        # Define test data
        X_test = pd.DataFrame({
            'feature1': [1, 2, 3],
            'feature2': [4, 5, 6]
        })
        y_test = pd.Series([110, 140, 210])

        # Call the main function
        evaluate_main('/dummy/model/path', X_test, y_test)

        # Assert that predictions and evaluation were run with the correct parameters
        mock_predictor_run.assert_called_once_with(X_test)
        mock_evaluator_run.assert_called_once_with(y_test, mock_predictor_run.return_value)

    @patch('ml.modeling.predictor.Predictor.run_predictions_for_evaluation')
    @patch('ml.modeling.evaluator.Evaluator.run')
    def test_prediction_error(self, mock_evaluator_run, mock_predictor_run):
        """
        Test handling of errors during prediction.
        """
        # Simulate an error
        mock_predictor_run.side_effect = Exception("Prediction error")

        X_test = pd.DataFrame({
            'feature1': [1, 2, 3],
            'feature2': [4, 5, 6]
        })
        y_test = pd.Series([110, 140, 210])

        # Call the main function and check for error logging
        with self.assertLogs(level='ERROR') as log:
            evaluate_main('/dummy/model/path', X_test, y_test)

        self.assertIn("An error occurred during model evaluation", log.output[0])

    @patch('ml.modeling.predictor.Predictor.run_predictions_for_evaluation')
    @patch('ml.modeling.evaluator.Evaluator.run')
    def test_evaluation_error(self, mock_evaluator_run, mock_predictor_run):
        """
        Test handling of errors during evaluation.
        """
        # Mock the predictions
        mock_predictor_run.return_value = pd.Series([100, 150, 200])

        # Simulate an error
        mock_evaluator_run.side_effect = Exception("Evaluation error")

        X_test = pd.DataFrame({
            'feature1': [1, 2, 3],
            'feature2': [4, 5, 6]
        })
        y_test = pd.Series([110, 140, 210])

        # Call the main function and check for error logging
        with self.assertLogs(level='ERROR') as log:
            evaluate_main('/dummy/model/path', X_test, y_test)

        self.assertIn("An error occurred during model evaluation", log.output[0])

if __name__ == '__main__':
    unittest.main()
