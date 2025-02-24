import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { AppComponent } from './app.component';
import { AppRoutingModule } from "./app.routing-module";
import { SharedModule } from "./shared/shared.module";
import { RouterModule } from "@angular/router";
import { provideHttpClient } from "@angular/common/http";
import { ApiService } from "./shared/services/api/services";
import { ApiConfiguration } from "./shared/services/api/api-configuration";
import { CommonModule } from "@angular/common";
import { NoopAnimationsModule } from "@angular/platform-browser/animations";

@NgModule({
  declarations: [
    AppComponent,
  ],
  imports: [
    AppRoutingModule,
    BrowserModule,
    NoopAnimationsModule,
    SharedModule,
    CommonModule,
    RouterModule,
  ],
  providers: [
    ApiService,
    ApiConfiguration,
    provideHttpClient()
  ],
  bootstrap: [AppComponent]
})
export class AppModule {}
