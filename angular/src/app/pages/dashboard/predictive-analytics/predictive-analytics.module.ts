import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { RouterModule } from "@angular/router";
import { PredictiveAnalyticsRoutingModule } from "./predictive-analytics-routing.module";
import { PredictiveAnalyticsPage } from "./predictive-analytics.page";
import { SharedModule } from "../../../shared/shared.module";
import { OverviewDemandForecastPage } from "./overview-demand-forecast/overview-demand-forecast.page";
import { LineChartModule } from "@swimlane/ngx-charts";

const PAGES = [
  PredictiveAnalyticsPage,
  OverviewDemandForecastPage,
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
    ]
})
export class PredictiveAnalyticsModule {}
