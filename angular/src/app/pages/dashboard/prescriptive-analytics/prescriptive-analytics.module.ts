import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { RouterModule } from "@angular/router";
import { PrescriptiveAnalyticsRoutingModule } from "./prescriptive-analytics-routing.module";
import { ReorderingPage } from "./reordering/reordering.page";
import { ComponentsModule } from "../../../shared/components/components.module";
import { ReorderInfoModalComponent } from "./modals/product-performance-modal/reorder-info-modal.component";

const MODALS = [
  ReorderInfoModalComponent,
]
const PAGES = [
  ReorderingPage,
];

@NgModule({
  declarations: [
    ...PAGES,
    ...MODALS,
  ],
  imports: [
    PrescriptiveAnalyticsRoutingModule,
    CommonModule,
    RouterModule,
    ComponentsModule,
  ]
})
export class PrescriptiveAnalyticsModule {}
