import { Injectable, ComponentFactoryResolver, ApplicationRef, Injector, EmbeddedViewRef, ComponentRef } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class ModalService {
  private modalComponentRef: ComponentRef<any> | null = null;
  public data: any;

  constructor(
    private componentFactoryResolver: ComponentFactoryResolver,
    private appRef: ApplicationRef,
    private injector: Injector
  ) {}

  open(component: any, data?: any): void {
    if (this.modalComponentRef) {
      this.close();
    }

    this.data = data;

    const componentFactory = this.componentFactoryResolver.resolveComponentFactory(component);
    this.modalComponentRef = componentFactory.create(this.injector);

    this.appRef.attachView(this.modalComponentRef.hostView);

    const domElem = (this.modalComponentRef.hostView as EmbeddedViewRef<any>).rootNodes[0] as HTMLElement;

    document.body.appendChild(domElem);
  }


  close(): void {
    if (this.modalComponentRef) {
      this.appRef.detachView(this.modalComponentRef.hostView);
      this.modalComponentRef.destroy();
      this.modalComponentRef = null;
    }
    this.data = null;
  }
}
