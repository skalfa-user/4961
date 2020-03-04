import { NgModule, isDevMode } from '@angular/core';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { IonicApp, IonicModule } from 'ionic-angular';
import { Keyboard } from '@ionic-native/keyboard';
import { TranslateModule } from 'ng2-translate/ng2-translate';
import { NgRedux, NgReduxModule, DevToolsExtension } from '@angular-redux/store';
import { BrowserModule } from '@angular/platform-browser';
import { App } from './app.component';
import { ProgressHttpModule } from 'angular-progress-http';
import { SwingModule } from 'angular2-swing';
import { VirtualScrollerModule } from 'ngx-virtual-scroller';
import { InAppPurchase } from '@ionic-native/in-app-purchase';
import { AdMobFree } from '@ionic-native/admob-free';
import { Push } from '@ionic-native/push';
import { Device } from '@ionic-native/device';
import { Facebook } from '@ionic-native/facebook';
import { Insomnia } from '@ionic-native/insomnia/ngx';

// store
import { rootReducer, IAppState, INITIAL_STATE } from 'store';

// services
import * as appServices from 'app/app.services';

// shared
import * as appShared from 'app/app.shared';

// pages
import * as appPages from 'app/app.pages';

@NgModule({
    declarations: [ // register all components
        ...appPages.declarationsList,
        ...appShared.declarationsList
    ],
    imports: [
        BrowserModule,
        BrowserAnimationsModule,
        SwingModule,
        IonicModule.forRoot(App, {
            scrollAssist: false,
            autoFocusAssist: false
        }),
        TranslateModule.forRoot(),
        NgReduxModule,
        ProgressHttpModule,
        VirtualScrollerModule
    ],
    bootstrap: [IonicApp],
    entryComponents: [ //  components that are used in router configurations.
        ...appPages.entryComponents,
        ...appShared.entryComponents
    ],
    providers: [ // make globally registered
        Keyboard,
        InAppPurchase,
        AdMobFree,
        Push,
        Device,
        Facebook,
        Insomnia,
        {  
            provide: 'virtualScroller.checkResizeInterval', 
            useValue: 0  
        },
        ...appServices.list
    ]
})

/**
 * App module
 */
export class AppModule {
    constructor(
        private ngRedux: NgRedux<IAppState>, 
        private devTools: DevToolsExtension) 
    {
        let enhancers = [];

        if (isDevMode() && devTools.isEnabled()) {
            enhancers = [ ...enhancers, this.devTools.enhancer() ];
        }

        this.ngRedux.configureStore(rootReducer, INITIAL_STATE, [], enhancers);
    }
}
