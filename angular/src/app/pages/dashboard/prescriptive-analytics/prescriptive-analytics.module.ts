import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { RouterModule } from "@angular/router";
import { PrescriptiveAnalyticsRoutingModule } from "./prescriptive-analytics-routing.module";
import { ReorderingPage } from "./reordering/reordering.page";
import { ComponentsModule } from "../../../shared/components/components.module";

const PAGES = [
  ReorderingPage,
];

@NgModule({
  declarations: [
    ...PAGES,
  ],
  imports: [
    PrescriptiveAnalyticsRoutingModule,
    CommonModule,
    RouterModule,
    ComponentsModule,
  ]
})
export class PrescriptiveAnalyticsModule {}
