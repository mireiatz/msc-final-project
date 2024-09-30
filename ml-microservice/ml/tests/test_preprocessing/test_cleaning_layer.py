import unittest
import pandas as pd
import numpy as np
import hashlib
from ml.preprocessing.cleaning_layer import CleaningLayer

class TestCleaningLayer(unittest.TestCase):

    def setUp(self):
        """
        Set up test CleaningLayer and data sample.
        """
        # Sample data used throughout the tests
        self.data = pd.DataFrame({
            'product_id': [None, None, '1234', 'ABC', '1234', '1234', '1234', '1234', '1234', '1234'],
            'product_name': ['Product A', 'Product B', 'Product A', 'Product C', 'Product A', 'Product A', 'Product A', 'Product A', 'Product A', 'Product A'],
            'category': ['cat & 1', 'cat / 2', 'cat & 1', 'CAT', 'cat & 1', 'cat & 1', 'cat & 1', 'cat & 1', 'cat & 1', 'cat & 1'],
            'monday': [1.5, -2, -130.3, 34, 0, 0, 0, 0, 0, 0],
            'tuesday': [-1, 2, None, 0, 0, 0, 0, 0, 0, 0],
            'wednesday': [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            'thursday': [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            'friday': [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            'saturday': [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            'sunday': [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            'quantity': [0, 0, 0, 0, 1000, 0, 0, 0, 0, 0],
            'value': [12.45, -12.30, -50, 0, 0, 0, 0, 0, 0, 0],
            'in_stock': [-1.2, 1.2, -50, 20, 0, 1, -0, -1, 10, 5],
            'year': [2021] * 10,
            'week': [1, 1, 2, 3, 3, 4, 5, 6, 7, 8]
        })

        # Initialise the layer
        self.layer = CleaningLayer(self.data)

    def test_validate_columns(self):
        """
        Test that if all columns are present in the DataFrame, no exception is raised.
        """
        try:
            # Invoke the method
            df = self.layer.validate_columns(self.data, ['product_id', 'product_name', 'category', 'week', 'year', 'monday', 'tuesday', 'wednesday','thursday', 'friday', 'saturday', 'sunday', 'value', 'in_stock'])
        except Exception as e:
            # Fail the test if any exception is raised
            self.fail(f"validate_columns raised {type(e).__name__} unexpectedly")

    def test_cast_integer_columns(self):
        """
        Test that default 'year' and 'week' columns are converted to integers from decimals.
        """
        # Define decimal weeks adn years
        test_data =  pd.DataFrame({
            'year': [2021.0, 2022.0, 2023.0, 2024.0],
            'week': [1.0, 15.0, 38.0, 52.0]
        })

        # Invoke method from the class
        df = self.layer.cast_integer_columns(test_data)

        self.assertListEqual(test_data['year'].tolist(), [2021, 2022, 2023, 2024])
        self.assertListEqual(test_data['week'].tolist(), [1, 15, 38, 52])

    def test_validate_columns_raises_error(self):
        """
        Test that a KeyError is raised when required columns are missing.
        """
        # Remove some columns from the sample data to simulate missing columns
        data_with_missing_columns = self.data.drop(columns=['product_name', 'category'])

        with self.assertRaises(KeyError):
            self.layer.validate_columns(data_with_missing_columns, ['product_id', 'product_name', 'category', 'week', 'year', 'monday', 'tuesday', 'wednesday','thursday', 'friday', 'saturday', 'sunday', 'value', 'in_stock'])

    def test_standardise_category_column(self):
        """
        Test that the 'category' column is standardised.
        """
        # Replace categories in the sample data to test for more scenarios
        test_data = self.data.copy()
        test_data['category'] = [
            'CAT & 1 / A * x',  # special characters, uppercase, underscores
            'pet food',  # specific case
            'petfood',  # specific case
            'sauces_pickle',  # specific case
            'sauces_pickles',  # specific case
            'washing_powder',  # specific case
            'washing_powders',  # specific case
            'lower_rate',  # specific case
            'standard_rate',  # specific case
            'zero_rate',  # specific case
        ]

        # Invoke method from the class
        df = self.layer.standardise_category_column(test_data)
        actual_categories = df['category'].tolist()
        expected_cleaned_categories = [
            'cat_1_a_x',
            'pet_food',
            'pet_food',
            'sauces_pickles',
            'sauces_pickles',
            'washing_powders',
            'washing_powders',
            'miscellaneous',
            'miscellaneous',
            'miscellaneous',
        ]
        self.assertListEqual(actual_categories, expected_cleaned_categories)

    def test_drop_duplicates(self):
        # Sample data with multiple duplicates
        test_data = pd.DataFrame({
            'product_id': ['A',  'A',  'A', 'B',  'B',  'C',  'C',  'C'],
            'week':       [21,   21,   22,   45,   45,   1,    1,    1],
            'year':       [2021, 2021, 2022, 2022, 2022, 2022, 2023, 2023],
        })

        # Invoke method from the class
        df = self.layer.drop_duplicates(test_data)

        # No instances of exact 'product_id', 'week' and 'year' matches
        expected_data = pd.DataFrame({
            'product_id': ['A',  'A',  'B',  'C', 'C'],
            'week':       [21,   22,   45,   1,    1],
            'year':       [2021, 2022, 2022, 2022, 2023],
        })
        pd.testing.assert_frame_equal(
            df.reset_index(drop=True),
            expected_data.reset_index(drop=True)
        )

    def test_generate_unique_id(self):
        """
        Test the generation of unique IDs based on product name and category.
        """
        # Invoke method from the class for different product names and categories
        actual_id = self.layer.generate_unique_id('Product A', 'cat1')
        expected_id = hashlib.md5('Product A_cat1'.encode()).hexdigest()[:8]
        self.assertEqual(actual_id, expected_id)

        actual_id = self.layer.generate_unique_id('Product B', 'cat2')
        expected_id = hashlib.md5('Product B_cat2'.encode()).hexdigest()[:8]
        self.assertEqual(actual_id, expected_id)

        actual_id = self.layer.generate_unique_id('Product C', 'CAT')
        expected_id = hashlib.md5('Product C_CAT'.encode()).hexdigest()[:8]
        self.assertEqual(actual_id, expected_id)

    def test_handle_product_ids(self):
        """
        Test the handling of the product IDs.
        """
        # Make a copy of the original product ids
        original_product_ids = self.data['product_id'].tolist()

        # Invoke method from the class
        df = self.layer.handle_product_ids(self.data.copy())

        # The original 'product_id' column was transferred to 'original_product_id'
        self.assertIn('original_product_id', df.columns)
        self.assertEqual(original_product_ids, df['original_product_id'].tolist())

        # Product IDs have been updated
        self.assertNotEqual(df['product_id'].tolist(), original_product_ids)

        # There should be 3 unique product IDs
        self.assertEqual(len(df['product_id'].unique()), 3)

    def test_calculate_cutoff_date(self):
        """
        Test that the correct cut off year and week are calculated accurately.
        """
        # Example parameters for within the year cut off date
        cutoff_period_weeks = 4
        year = 2021
        week = 9

        # Invoke method from the class
        cutoff_year, cutoff_week = self.layer.calculate_cutoff_date(year, week, cutoff_period_weeks)

        # Calculate expected values manually (2021, week 5, assuming no year rollover)
        expected_cutoff_year = 2021
        expected_cutoff_week = 5

        # Assert the cutoff date is correct
        self.assertEqual(cutoff_year, expected_cutoff_year)
        self.assertEqual(cutoff_week, expected_cutoff_week)

        # Example parameters for cut off date in previous year
        week = 3

        # Invoke method from the class
        cutoff_year, cutoff_week = self.layer.calculate_cutoff_date(year, week, cutoff_period_weeks)

        # Calculate expected values manually (2021, week 5, assuming no year rollover)
        expected_cutoff_year = 2020
        expected_cutoff_week = 52

        # Assert the cutoff date is correct
        self.assertEqual(cutoff_year, expected_cutoff_year)
        self.assertEqual(cutoff_week, expected_cutoff_week)

    def test_remove_inactive_products(self):
        """
        Test that products without sales in the last few weeks are removed correctly.
        """
        # Example parameter to remove products inactive the last 2 weeks
        cutoff_period_weeks = 2

        # Invoke method from the class
        df = self.layer.remove_inactive_products(self.data, cutoff_period_weeks)

        # The product 'ABC' is removed
        self.assertNotIn('ABC', df['product_id'].values)

        # The product '1234' is still in the dataset
        self.assertIn('1234', df['product_id'].values)

    def test_clean_sales_columns(self):
        """
        Test that sales columns 'monday' to 'sunday' are cleaned.
        """
        # Invoke method from the class
        df = self.layer.clean_sales_columns(self.data.copy())

        # Sales values are absolute integers
        self.assertEqual(df['monday'].iloc[0], 1)
        self.assertEqual(df['monday'].iloc[1], 2)
        self.assertEqual(df['monday'].iloc[2], 130)
        self.assertEqual(df['monday'].iloc[3], 34)
        self.assertEqual(df['monday'].iloc[4], 0)
        self.assertEqual(df['monday'].iloc[5], 0)
        self.assertEqual(df['monday'].iloc[6], 0)
        self.assertEqual(df['monday'].iloc[7], 0)
        self.assertEqual(df['monday'].iloc[8], 0)
        self.assertEqual(df['monday'].iloc[9], 0)

        self.assertEqual(df['tuesday'].iloc[0], 1)
        self.assertEqual(df['tuesday'].iloc[1], 2)
        self.assertEqual(df['tuesday'].iloc[2], 0)
        self.assertEqual(df['tuesday'].iloc[3], 0)
        self.assertEqual(df['tuesday'].iloc[4], 0)
        self.assertEqual(df['tuesday'].iloc[5], 0)
        self.assertEqual(df['tuesday'].iloc[6], 0)
        self.assertEqual(df['tuesday'].iloc[7], 0)
        self.assertEqual(df['tuesday'].iloc[8], 0)
        self.assertEqual(df['tuesday'].iloc[9], 0)

        self.assertEqual(df['wednesday'].iloc[0], 0)
        self.assertEqual(df['wednesday'].iloc[1], 0)
        self.assertEqual(df['wednesday'].iloc[2], 0)
        self.assertEqual(df['wednesday'].iloc[3], 0)
        self.assertEqual(df['wednesday'].iloc[4], 0)
        self.assertEqual(df['wednesday'].iloc[5], 0)
        self.assertEqual(df['wednesday'].iloc[6], 0)
        self.assertEqual(df['wednesday'].iloc[7], 0)
        self.assertEqual(df['wednesday'].iloc[8], 0)
        self.assertEqual(df['wednesday'].iloc[9], 0)

        self.assertEqual(df['thursday'].iloc[0], 0)
        self.assertEqual(df['thursday'].iloc[1], 0)
        self.assertEqual(df['thursday'].iloc[2], 0)
        self.assertEqual(df['thursday'].iloc[3], 0)
        self.assertEqual(df['thursday'].iloc[4], 0)
        self.assertEqual(df['thursday'].iloc[5], 0)
        self.assertEqual(df['thursday'].iloc[6], 0)
        self.assertEqual(df['thursday'].iloc[7], 0)
        self.assertEqual(df['thursday'].iloc[8], 0)
        self.assertEqual(df['thursday'].iloc[9], 0)

        self.assertEqual(df['friday'].iloc[0], 0)
        self.assertEqual(df['friday'].iloc[1], 0)
        self.assertEqual(df['friday'].iloc[2], 0)
        self.assertEqual(df['friday'].iloc[3], 0)
        self.assertEqual(df['friday'].iloc[4], 0)
        self.assertEqual(df['friday'].iloc[5], 0)
        self.assertEqual(df['friday'].iloc[6], 0)
        self.assertEqual(df['friday'].iloc[7], 0)
        self.assertEqual(df['friday'].iloc[8], 0)
        self.assertEqual(df['friday'].iloc[9], 0)

        self.assertEqual(df['saturday'].iloc[0], 0)
        self.assertEqual(df['saturday'].iloc[1], 0)
        self.assertEqual(df['saturday'].iloc[2], 0)
        self.assertEqual(df['saturday'].iloc[3], 0)
        self.assertEqual(df['saturday'].iloc[4], 0)
        self.assertEqual(df['saturday'].iloc[5], 0)
        self.assertEqual(df['saturday'].iloc[6], 0)
        self.assertEqual(df['saturday'].iloc[7], 0)
        self.assertEqual(df['saturday'].iloc[8], 0)
        self.assertEqual(df['saturday'].iloc[9], 0)

        self.assertEqual(df['sunday'].iloc[0], 0)
        self.assertEqual(df['sunday'].iloc[1], 0)
        self.assertEqual(df['sunday'].iloc[2], 0)
        self.assertEqual(df['sunday'].iloc[3], 0)
        self.assertEqual(df['sunday'].iloc[4], 0)
        self.assertEqual(df['sunday'].iloc[5], 0)
        self.assertEqual(df['sunday'].iloc[6], 0)
        self.assertEqual(df['sunday'].iloc[7], 0)
        self.assertEqual(df['sunday'].iloc[8], 0)
        self.assertEqual(df['sunday'].iloc[9], 0)

    def test_clean_quantity_column(self):
        """
        Test that 'quantity' is correctly recalculated.
        """
        # Invoke method from the class
        df = self.layer.clean_quantity_column(self.data.copy())

        # Quantity values are the sum of 'monday-sunday'
        self.assertEqual(df['quantity'].iloc[0], 0.5)
        self.assertEqual(df['quantity'].iloc[1], 0)
        self.assertEqual(df['quantity'].iloc[2], -130.3)
        self.assertEqual(df['quantity'].iloc[3], 34)
        self.assertEqual(df['quantity'].iloc[4], 0)
        self.assertEqual(df['quantity'].iloc[5], 0)
        self.assertEqual(df['quantity'].iloc[6], 0)
        self.assertEqual(df['quantity'].iloc[7], 0)
        self.assertEqual(df['quantity'].iloc[8], 0)
        self.assertEqual(df['quantity'].iloc[9], 0)

    def test_clean_price_column(self):
        """
        Test that the price indicating column is cleaned.
        """
        # Invoke method from the class
        df = self.layer.clean_price_column(self.data.copy())

        # Values are absolutes
        self.assertEqual(df['value'].iloc[0], 12.45)
        self.assertEqual(df['value'].iloc[1], 12.3)
        self.assertEqual(df['value'].iloc[2], 50)
        self.assertEqual(df['value'].iloc[3], 0)
        self.assertEqual(df['value'].iloc[4], 0)
        self.assertEqual(df['value'].iloc[5], 0)
        self.assertEqual(df['value'].iloc[6], 0)
        self.assertEqual(df['value'].iloc[7], 0)
        self.assertEqual(df['value'].iloc[8], 0)
        self.assertEqual(df['value'].iloc[9], 0)

    def test_standardise_in_stock(self):
        """
        Test that the 'in_stock' column is standardised to binary format (0 or 1).
        """
        # Invoke method from the class
        df = self.layer.standardise_in_stock(self.data.copy())

        # < 0 values are 0, > 1 values are 1
        actual_in_stock = df['in_stock'].tolist()
        expected_in_stock = [0, 1, 0, 1, 0, 1, 0, 0, 1, 1]
        self.assertEqual(actual_in_stock, expected_in_stock, "In_stock standardisation failed")

    def test_insert_missing_product_weekly(self):
        """
        Test that rows are inserted for missing weeks per product.
        """
        # Fixes on data to bypass other cleaning operations
        test_data = self.data.copy()
        test_data['product_id'] = ['product_a', 'product_b', 'product_a', 'product_c', 'product_a', 'product_a', 'product_a', 'product_a', 'product_a', 'product_a']
        test_data['monday'] = [1, 2, 130, 34, 0, 0, 0, 0, 0, 0]
        test_data['tuesday'] = [1, 2, 0, 0, 0, 0, 0, 0, 0, 0]
        test_data['quantity'] = [2, 4, 130, 34, 0, 0, 0, 0, 0, 0]
        test_data['in_stock'] = [1, 1, 50, 20, 0, 1, 0, 1, 10, 5]

        # New products to test correct 'in_stock' forward filling
        new_product_data = pd.DataFrame({
            'product_id': ['product_c'] * 2,
            'product_name': ['Product C'] * 2,
            'category': ['CAT'] * 2,
            'monday': [50, 30],
            'tuesday': [40, 20],
            'wednesday': [0, 0],
            'thursday': [0, 0],
            'friday': [0, 0],
            'saturday': [0, 0],
            'sunday': [0, 0],
            'quantity': [90, 50],
            'value': [100.0, 60.0],
            'in_stock': [1, 5],
            'year': [2021] * 2,
            'week': [5, 7]
        })

        # Append the new products to the test data
        test_data = pd.concat([test_data, new_product_data], ignore_index=True)

        # Invoke method from the class
        df = self.layer.insert_missing_product_weekly(test_data)

        # Product A remains unchanged
        expected_product_a = test_data[test_data['product_name'] == 'Product A']
        actual_product_a = df[df['product_name'] == 'Product A']
        self.assertEqual(len(expected_product_a), len(actual_product_a))
        pd.testing.assert_frame_equal(
            expected_product_a[sorted(expected_product_a.columns)].reset_index(drop=True),
            actual_product_a[sorted(actual_product_a.columns)].reset_index(drop=True)
        )

        # Product B contains the original week 1 plus new weeks 2-8 with 0 sales
        expected_product_b = pd.DataFrame({
            'product_id': ['product_b'] * 8,
            'product_name': ['Product B'] * 8,
            'category': ['cat / 2'] * 8,
            'monday': [2, 0, 0, 0, 0, 0, 0, 0],
            'tuesday': [2, 0, 0, 0, 0, 0, 0, 0],
            'wednesday': [0] * 8,
            'thursday': [0] * 8,
            'friday': [0] * 8,
            'saturday': [0] * 8,
            'sunday': [0] * 8,
            'quantity': [4, 0, 0, 0, 0, 0, 0, 0],
            'value': [-12.30, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
            'in_stock': [1, 1, 1, 1, 1, 1, 1, 1],  # in_stock carried forward from week 1
            'year': [2021] * 8,
            'week': [1, 2, 3, 4, 5, 6, 7, 8]
        })
        actual_product_b = df[df['product_name'] == 'Product B']
        self.assertEqual(len(expected_product_b), len(actual_product_b))
        pd.testing.assert_frame_equal(
            expected_product_b[sorted(expected_product_b.columns)].reset_index(drop=True),
            actual_product_b[sorted(actual_product_b.columns)].reset_index(drop=True)
        )

        # Product C contains the original week 3, plus new weeks 1, 2, and 4-8 with 0 sales
        expected_product_c = pd.DataFrame({
            'product_id': ['product_c'] * 8,
            'product_name': ['Product C'] * 8,
            'category': ['CAT'] * 8,
            'monday': [0, 0, 34, 0, 50, 0, 30, 0],
            'tuesday': [0, 0, 0, 0, 40, 0, 20, 0],
            'wednesday': [0] * 8,
            'thursday': [0] * 8,
            'friday': [0] * 8,
            'saturday': [0] * 8,
            'sunday': [0] * 8,
            'quantity': [0, 0, 34, 0, 90, 0, 50, 0],
            'value': [0.0, 0.0, 0.0, 0.0, 100.0, 0.0, 60.0, 0.0],
            'in_stock': [0, 0, 20, 20, 1, 1, 5, 5],  # in_stock carried forward from week 1
            'year': [2021] * 8,
            'week': [1, 2, 3, 4, 5, 6, 7, 8]
        })
        actual_product_c = df[df['product_name'] == 'Product C']
        self.assertEqual(len(expected_product_c), len(actual_product_c))
        pd.testing.assert_frame_equal(
            expected_product_c[sorted(expected_product_c.columns)].reset_index(drop=True),
            actual_product_c[sorted(actual_product_c.columns)].reset_index(drop=True)
        )

if __name__ == '__main__':
    unittest.main()
