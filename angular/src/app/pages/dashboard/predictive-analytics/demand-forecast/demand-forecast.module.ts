import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { DemandForecastPage } from "./demand-forecast.page";
import { RouterModule } from "@angular/router";
import { DemandForecastRoutingModule } from "./demand-forecast-routing.module";

const PAGES = [
  DemandForecastPage,
];

@NgModule({
  declarations: [
    ...PAGES,
  ],
  imports: [
    DemandForecastRoutingModule,
    CommonModule,
    RouterModule,
  ]
})
export class DemandForecastModule {}
