import { RouterModule, Routes } from "@angular/router";
import { NgModule } from "@angular/core";

const routes: Routes = [
  {
    path: '',
    redirectTo: '/demand-forecast',
    pathMatch: 'full'
  },
  {
    path: 'demand-forecast',
    loadChildren: () => import('./demand-forecast/demand-forecast.module').then(s => s.DemandForecastModule),
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class PredictiveAnalyticsRoutingModule {}
