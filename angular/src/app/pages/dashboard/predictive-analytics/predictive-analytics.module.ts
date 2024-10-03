import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { RouterModule } from "@angular/router";
import { PredictiveAnalyticsRoutingModule } from "./predictive-analytics-routing.module";
import { PredictiveAnalyticsPage } from "./predictive-analytics.page";
import { SharedModule } from "../../../shared/shared.module";
import { OverviewDemandForecastPage } from "./overview-demand-forecast/overview-demand-forecast.page";
import { LineChartModule } from "@swimlane/ngx-charts";
import { CategoryDemandForecastPage } from "./category-demand-forecast/category-demand-forecast.page";
import { NgSelectComponent } from "@ng-select/ng-select";
import { FormsModule } from "@angular/forms";

const PAGES = [
  PredictiveAnalyticsPage,
  OverviewDemandForecastPage,
  CategoryDemandForecastPage,
];

@NgModule({
  declarations: [
    ...PAGES,
  ],
  imports: [
    PredictiveAnalyticsRoutingModule,
    CommonModule,
    RouterModule,
    SharedModule,
    LineChartModule,
    FormsModule,
    NgSelectComponent,
  ]
})
export class PredictiveAnalyticsModule {}
