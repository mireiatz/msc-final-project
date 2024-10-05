import unittest
from unittest.mock import patch, MagicMock
import pandas as pd
from ml.modeling.demand_predictor import DemandPredictor
import joblib

class TestDemandPredictor(unittest.TestCase):

    @patch('joblib.load')
    def test_load_model_success(self, mock_joblib_load):
        """
        Test that the model is successfully loaded.
        """
        # Mock the joblib load method
        mock_joblib_load.return_value = MagicMock()

        predictor = DemandPredictor(model_path='/dummy/path/to/model')
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

        predictor = DemandPredictor(model_path='/dummy/path/to/model')

        # Assert that the RuntimeError is raised
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
        predictor = DemandPredictor()
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
        Test that predictions are transformed correctly.
        """
        # Sample predictions and input data
        predictions = [100.6, -50.2]
        source_product_ids = ['A', 'B']
        dates = ['2023-01-01', '2023-01-02']

        # Transform predictions
        predictor = DemandPredictor()
        result_df = predictor.transform_predictions(predictions, source_product_ids, dates)

        # Check that the DataFrame is formatted correctly
        expected_df = pd.DataFrame({
            'product_id': ['A', 'B'],
            'date': ['2023-01-01', '2023-01-02'],
            'value': [101, 0]  # Rounded and negative value clipped
        })

        pd.testing.assert_frame_equal(result_df, expected_df)

    @patch('joblib.load')
    @patch.object(DemandPredictor, 'make_predictions')
    def test_run_pipeline_success(self, mock_make_predictions, mock_joblib_load):
        """
        Test the full pipeline from loading the model to returning predictions.
        """
        # Mock model loading
        mock_joblib_load.return_value = MagicMock()

        # Mock prediction
        mock_make_predictions.return_value = [100, 200]


        # Sample data
        data = pd.DataFrame({'feature1': [1, 2], 'feature2': [3, 4]})
        source_product_ids = ['A', 'B']
        dates = ['2023-01-01', '2023-01-02']

        # Run the predictor
        predictor = DemandPredictor()
        predictions_df = predictor.run(data, source_product_ids, dates)

        # Check that predictions are returned and formatted correctly
        expected_df = pd.DataFrame({
            'product_id': ['A', 'B'],
            'date': ['2023-01-01', '2023-01-02'],
            'value': [100, 200]
        })

        pd.testing.assert_frame_equal(predictions_df, expected_df)

    def test_run_empty_data(self):
        """
        Test that ValueError is raised for empty DataFrame.
        """
        predictor = DemandPredictor()

        # Run with an empty DataFrame
        with self.assertRaises(ValueError) as context:
            predictor.run(pd.DataFrame(), ['A', 'B'], ['2023-01-01', '2023-01-02'])

        self.assertIn("Empty DataFrame provided", str(context.exception))

    def test_run_invalid_data_type(self):
        """
        Test that ValueError is raised for non-DataFrame inputs.
        """
        predictor = DemandPredictor()

        # Run with invalid data type
        with self.assertRaises(ValueError) as context:
            predictor.run("invalid_data", ['A', 'B'], ['2023-01-01', '2023-01-02'])

        self.assertIn("Expected data as a DataFrame", str(context.exception))

if __name__ == '__main__':
    unittest.main()
