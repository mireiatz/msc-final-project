import { RouterModule, Routes } from "@angular/router";
import { NgModule } from "@angular/core";
import { CategoryLevelDemandForecastPage } from "./category-level-demand-forecast/category-level-demand-forecast.page";
import { PredictiveAnalyticsPage } from "./predictive-analytics.page";
import { ProductLevelDemandForecastPage } from "./product-level-demand-forecast/product-level-demand-forecast.page";
import { MonthDemandForecastPage } from "./month-demand-forecast/month-demand-forecast.page";
import { WeeklyDemandForecastPage } from "./weekly-demand-forecast/weekly-demand-forecast.page";

const routes: Routes = [
  {
    path: '',
    component: PredictiveAnalyticsPage,
    children: [
      {
        path: '',
        redirectTo: 'category-level',
        pathMatch: 'full'
      },
      {
        path: 'category-level',
        component: CategoryLevelDemandForecastPage,
      },
      {
        path: 'product-level',
        component: ProductLevelDemandForecastPage,
      },
      {
        path: 'weekly',
        component: WeeklyDemandForecastPage,
      },
      {
        path: 'month',
        component: MonthDemandForecastPage,
      },
    ],
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class PredictiveAnalyticsRoutingModule {}
