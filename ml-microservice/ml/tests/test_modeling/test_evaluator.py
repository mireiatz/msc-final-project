import unittest
from unittest.mock import patch, MagicMock
import numpy as np
from ml.modeling.evaluator import Evaluator

class TestEvaluator(unittest.TestCase):

    def setUp(self):
        """
        Set the Evaluator and test data.
        """
        self.evaluator = Evaluator(target='quantity')
        self.y_test = np.array([3, -0, 2, 7])
        self.predictions = np.array([2.5, 0.0, 2, 8])

    def test_calculate_metrics(self):
        """
        Test to check the calculations are accurate.
        """
        # Invoke the class method
        mae, rmse, r2 = self.evaluator.calculate_metrics(self.y_test, self.predictions)

        # Check metrics
        self.assertAlmostEqual(mae, 0.375, places=3)
        self.assertAlmostEqual(rmse, 0.559, places=3)
        self.assertAlmostEqual(r2, 0.951923, places=3)

    @patch('ml.modeling.evaluator.logging.info')
    def test_run(self, mock_logging_info):
        # Invoke the class method
        metrics = self.evaluator.run(self.y_test, self.predictions)

        # Check metrics
        self.assertAlmostEqual(metrics['mae'], 0.375, places=3)
        self.assertAlmostEqual(metrics['rmse'], 0.559, places=3)
        self.assertAlmostEqual(metrics['r2'], 0.951923, places=3)

if __name__ == '__main__':
    unittest.main()
