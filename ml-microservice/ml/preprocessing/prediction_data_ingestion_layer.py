import logging
import os
import pandas as pd

class PredictionDataIngestionLayer:

    def __init__(self, historical_data, prediction_dates):
        self.historical_data = historical_data
        self.prediction_dates = prediction_dates

    def process(self):
        """
        Process the prediction data using a dictionary of lists to create a DataFrame.
        """
        logging.info("Starting ingestion of prediction data process...")

        prediction_data = []

        # Get a record per product from the historical sales
        products = self.historical_data[['source_product_id', 'product_name', 'category', 'per_item_value', 'in_stock']].drop_duplicates()

        # For each unique product, append the prediction dates
        for _, product in products.iterrows():
            for date in self.prediction_dates:
                prediction_data.append({
                    'source_product_id': product['source_product_id'],
                    'product_name': product['product_name'],
                    'category': product['category'],
                    'per_item_value': product['per_item_value'],
                    'in_stock': product['in_stock'],
                    'date': date,
                    'quantity': None  # No sales data for future prediction dates
                })

        # Convert the prediction data to a DataFrame
        df_prediction = pd.DataFrame(prediction_data)

        # Combine historical and prediction data into a DataFrames
        df = pd.concat([self.historical_data, df_prediction], ignore_index=True)

        logging.info("Ingestion completed")

        return df
