import { RouterModule, Routes } from "@angular/router";
import { NgModule } from "@angular/core";

const routes: Routes = [
  {
    path: '',
    redirectTo: '/descriptive-analytics',
    pathMatch: 'full'
  },
  {
    path: 'descriptive-analytics',
    loadChildren: () => import('./descriptive-analytics/descriptive-analytics.module').then(s => s.DescriptiveAnalyticsModule),
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DashboardRoutingModule {}
