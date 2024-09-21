import { RouterModule, Routes } from "@angular/router";
import { NgModule } from "@angular/core";
import { SalesPage } from "./sales/sales.page";
import { OverviewPage } from "./overview/overview.page";
import { ProductsPerformancePage } from "./products-performance/products-performance.page";
import { StockLevelsPage } from "./stock-levels/stock-levels.page";
import { DescriptiveAnalyticsPage } from "./descriptive-analytics.page";

const routes: Routes = [
  {
    path: '',
    component: DescriptiveAnalyticsPage,
    children: [
      {
        path: '',
        redirectTo: 'overview',
        pathMatch: 'full'
      },
      {
        path: 'overview',
        component: OverviewPage,
      },
      {
        path: 'product-performance',
        component: ProductsPerformancePage,
      },
      {
        path: 'sales',
        component: SalesPage,
      },
      {
        path: 'stock-levels',
        component: StockLevelsPage,
      },
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DescriptiveAnalyticsRoutingModule {}
