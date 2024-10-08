import unittest
import pandas as pd
from ml.preprocessing.prediction_data_ingestion_layer import PredictionDataIngestionLayer

class TestPredictionDataIngestionLayer(unittest.TestCase):

    def setUp(self):
        """
        Set up test PredictionDataIngestionLayer and data sample.
        """
        # Define sample historical data
        self.historical_data = pd.DataFrame({
            'source_product_id': ['A', 'A', 'B', 'B'],
            'product_name': ['Product A', 'Product A', 'Product B', 'Product B'],
            'category': ['cat1', 'cat1', 'cat2', 'cat2'],
            'per_item_value': [100, 100, 200, 200],
            'in_stock': [1, 1, 1, 1],
            'date': ['2023-01-01', '2023-01-02', '2023-01-01', '2023-01-02'],
            'quantity': [100, 200, 150, 250]
        })

        # Define prediction dates
        self.prediction_dates = ['2023-01-03', '2023-01-04']

        # Initialise the layer with historical data and prediction dates
        self.layer = PredictionDataIngestionLayer(historical_data=self.historical_data, prediction_dates=self.prediction_dates)

    def test_process_ingestion(self):
        """
        Test that the PredictionDataIngestionLayer processes data correctly.
        """
        # Invoke the class method
        df = self.layer.process()

        # Define expected DataFrame
        expected_data = {
            'source_product_id': ['A', 'A', 'B', 'B', 'A', 'A', 'B', 'B'],
            'product_name': ['Product A', 'Product A', 'Product B', 'Product B', 'Product A', 'Product A', 'Product B', 'Product B'],
            'category': ['cat1', 'cat1', 'cat2', 'cat2', 'cat1', 'cat1', 'cat2', 'cat2'],
            'per_item_value': [100, 100, 200, 200, 100, 100, 200, 200],
            'in_stock': [1, 1, 1, 1, 1, 1, 1, 1],
            'date': ['2023-01-01', '2023-01-02', '2023-01-01', '2023-01-02', '2023-01-03', '2023-01-04', '2023-01-03', '2023-01-04'],
            'quantity': [100, 200, 150, 250, None, None, None, None]
        }
        expected_df = pd.DataFrame(expected_data)

        # Convert the 'quantity' column to float64 to ensure consistency in both DataFrames
        df['quantity'] = df['quantity'].astype('float64')
        expected_df['quantity'] = expected_df['quantity'].astype('float64')

        # Check that the data has been re-structured correctly during ingestion
        pd.testing.assert_frame_equal(df.reset_index(drop=True), expected_df)

if __name__ == '__main__':
    unittest.main()
