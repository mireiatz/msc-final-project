import { RouterModule, Routes } from "@angular/router";
import { NgModule } from "@angular/core";

const routes: Routes = [
  {
    path: '',
    redirectTo: '/reordering',
    pathMatch: 'full'
  },
  {
    path: 'reordering',
    loadChildren: () => import('./reordering/reordering.module').then(s => s.ReorderingModule),
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class PrescriptiveAnalyticsRoutingModule {}
