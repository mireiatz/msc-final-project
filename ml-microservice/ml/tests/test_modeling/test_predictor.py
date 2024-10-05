import unittest
from unittest.mock import patch, MagicMock
import pandas as pd
from ml.modeling.predictor import Predictor

class TestPredictor(unittest.TestCase):

    @patch('joblib.load')
    def test_load_model_success(self, mock_joblib_load):
        """
        Test that the model is successfully loaded.
        """
        # Mock the joblib load method
        mock_joblib_load.return_value = MagicMock()

        predictor = Predictor(model_path='/dummy/path/to/model')
        predictor.load_model()

        # Check that the model was set
        self.assertIsNotNone(predictor.model, "Model should be loaded")

    @patch('joblib.load')
    def test_load_model_failure(self, mock_joblib_load):
        """
        Test that an exception is raised when model loading fails.
        """
        # Simulate an error when loading the model
        mock_joblib_load.side_effect = Exception("Failed to load model")

        predictor = Predictor(model_path='/dummy/path/to/model')

        # Error raised
        with self.assertRaises(RuntimeError) as context:
            predictor.load_model()

        self.assertIn("Failed to load model", str(context.exception))

    def test_make_predictions_success(self):
        """
        Test that predictions are successfully made.
        """
        # Create a mock model with a predict method and set it in the predictor
        mock_model = MagicMock()
        mock_model.predict.return_value = [100, 200]
        predictor = Predictor()
        predictor.model = mock_model

        # Test data
        test_data = pd.DataFrame({
            'feature1': [1, 2],
            'feature2': [3, 4]
        })

        # Run prediction
        predictions = predictor.make_predictions(test_data)

        # Predictions are correct
        self.assertEqual(predictions, [100, 200], "Predictions should match the mock model output")

    def test_transform_predictions(self):
        """
        Test that predictions are transformed correctly for live predictions.
        """
        # Define sample predictions and input data
        predictions = [100.6, -50.2]
        source_product_ids = ['A', 'B']
        dates = ['2023-01-01', '2023-01-02']

        # Transform predictions
        predictor = Predictor()
        result_df = predictor.transform_predictions(predictions, source_product_ids, dates)

        # Check that the DataFrame is formatted correctly
        expected_df = pd.DataFrame({
            'product_id': ['A', 'B'],
            'date': ['2023-01-01', '2023-01-02'],
            'value': [101, 0]  # Rounded and negative value clipped
        })

        pd.testing.assert_frame_equal(result_df, expected_df)

    @patch('ml.modeling.predictor.Predictor.load_model')
    def test_run_predictions_for_evaluation(self, mock_load_model):
        """
        Test running predictions for evaluation purposes.
        """
        # Mock test data
        X_test = pd.DataFrame({
            'feature1': [1, 2],
            'feature2': [3, 4]
        })

        # Mock predictions
        mock_predictions = pd.Series([100, 200])

        # Instantiate predictor and mock predictions
        predictor = Predictor(model_path='/dummy/model_path.pkl')
        predictor.make_predictions = MagicMock(return_value=mock_predictions)

        # Invoke the class method
        predictions = predictor.run_predictions_for_evaluation(X_test)

        # Check the process characteristics
        self.assertTrue(isinstance(predictions, pd.Series))
        self.assertEqual(len(predictions), len(X_test))
        self.assertListEqual(predictions.tolist(), mock_predictions.tolist())

    @patch('ml.modeling.predictor.joblib.load')
    @patch.object(Predictor, 'make_predictions')
    def test_run_live_predictions(self, mock_make_predictions, mock_joblib_load):
        """
        Test live predictions.
        """
        # Mock the model and predictions
        mock_joblib_load.return_value = 'mock_model'
        mock_make_predictions.return_value = [10, 20, 30]

        # Instantiate predictor
        predictor = Predictor(model_path='dummy_path')

        # Invoke the class method with test data
        data = pd.DataFrame({'feature1': [1, 2, 3], 'feature2': [4, 5, 6]})
        predictions = predictor.run_live_predictions(data, ['A', 'B', 'C'], ['2023-01-01', '2023-01-02', '2023-01-03'])

        # Check the process characteristics
        mock_joblib_load.assert_called_once_with('dummy_path')
        mock_make_predictions.assert_called_once_with(data)
        self.assertEqual(len(predictions), 3)

    def test_sanity_check(self):
        """
        Test that errors are raised for invalid data input.
        """
        predictor = Predictor()

        # Error raised
        with self.assertRaises(ValueError) as context:
            predictor.sanity_check(pd.DataFrame())

        self.assertIn("Empty DataFrame provided", str(context.exception))

        # Error raised
        with self.assertRaises(ValueError) as context:
            predictor.sanity_check("invalid_data")

        self.assertIn("Expected data as a DataFrame", str(context.exception))

if __name__ == '__main__':
    unittest.main()
