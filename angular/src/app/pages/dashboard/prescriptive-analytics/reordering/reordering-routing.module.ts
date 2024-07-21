import { RouterModule, Routes } from "@angular/router";
import { NgModule } from "@angular/core";
import { ReorderingPage } from "./reordering.page";

const routes: Routes = [
  {
    path: '',
    component: ReorderingPage,
    pathMatch: 'full',
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class ReorderingRoutingModule {}
