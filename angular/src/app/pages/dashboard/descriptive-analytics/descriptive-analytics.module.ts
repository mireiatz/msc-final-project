import { CommonModule } from "@angular/common";
import { NgModule } from "@angular/core";
import { DescriptiveAnalyticsRoutingModule } from "./descriptive-analytics-routing.module";
import { RouterModule } from "@angular/router";
import { ProductsPerformancePage } from "./products-performance/products-performance.page";
import { OverviewPage } from "./overview/overview.page";
import { SalesPage } from "./sales/sales.page";
import { StockLevelsPage } from "./stock-levels/stock-levels.page";
import { DescriptiveAnalyticsPage } from "./descriptive-analytics.page";
import { SharedModule } from "../../../shared/shared.module";
import { NgxChartsModule } from "@swimlane/ngx-charts";
import { ProductPerformanceModalComponent } from "./modals/product-performance-modal/product-performance-modal.component";

const MODALS = [
  ProductPerformanceModalComponent,
]

const PAGES = [
  DescriptiveAnalyticsPage,
  OverviewPage,
  ProductsPerformancePage,
  SalesPage,
  StockLevelsPage,
];

@NgModule({
  declarations: [
    ...PAGES,
    ...MODALS,
  ],
  imports: [
    DescriptiveAnalyticsRoutingModule,
    CommonModule,
    RouterModule,
    SharedModule,
    NgxChartsModule,
  ],
})
export class DescriptiveAnalyticsModule {}
