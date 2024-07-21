import { SidebarComponent } from "./sidebar/sidebar.component";
import { NgModule } from "@angular/core";
import { CommonModule } from "@angular/common";
import { RouterModule } from "@angular/router";

const COMPONENTS = [
  SidebarComponent,
]
@NgModule({
  declarations: [
    ...COMPONENTS,
  ],
  imports: [
    CommonModule,
    RouterModule,
  ],
  providers: [],
  exports: [
    ...COMPONENTS,
  ]
})
export class ComponentsModule {}
