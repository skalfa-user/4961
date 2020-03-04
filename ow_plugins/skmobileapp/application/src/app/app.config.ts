import { InjectionToken } from '@angular/core';

export interface IApplicationConfig {
    id: string;
    version: string;
    name: string;
    description: string;
    authorEmail: string;
    authorName: string;
    authorUrl: string;
    serverUrl: string;
    facebookAppId: string;
    googleProjectNumber: string;
    playStoreKey: string;
    pwaBackgroundColor: string;
    pwaThemeColor: string;
    pwaIcon: string;
    pwaIconSize: string;
    pwaIconType: string;
    appleIcon: string;
    appleIconSize: string;
}

export const APPLICATION_CONFIG : IApplicationConfig = require('../../application.config.json');
export const APPLICATION_CONFIG_PROVIDER = new InjectionToken<IApplicationConfig>('app.config');
