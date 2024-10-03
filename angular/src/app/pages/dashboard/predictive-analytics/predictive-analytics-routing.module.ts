import { RouterModule, Routes } from "@angular/router";
import { NgModule } from "@angular/core";
import { OverviewDemandForecastPage } from "./overview-demand-forecast/overview-demand-forecast.page";
import { PredictiveAnalyticsPage } from "./predictive-analytics.page";
import { CategoryDemandForecastPage } from "./category-demand-forecast/category-demand-forecast.page";
import { MonthDemandForecastPage } from "./month-demand-forecast/month-demand-forecast.page";

const routes: Routes = [
  {
    path: '',
    component: PredictiveAnalyticsPage,
    children: [
      {
        path: '',
        redirectTo: 'overview',
        pathMatch: 'full'
      },
      {
        path: 'overview',
        component: OverviewDemandForecastPage,
      },
      {
        path: 'category-based',
        component: CategoryDemandForecastPage,
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
