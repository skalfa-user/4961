import { Injectable } from '@angular/core';
import { ReplaySubject } from 'rxjs/ReplaySubject'
import { Push, PushObject, PushOptions, Priority } from '@ionic-native/push';
import { Device as NativeDevice } from '@ionic-native/device';
import { Observable } from 'rxjs/Observable';

// services
import { ApplicationService } from 'services/application'
import { SecureHttpService} from 'services/http'
import { PersistentStorageService } from 'services/persistent-storage';

@Injectable()
export class PushNotificationsService {
    private isInit: boolean = false;
    private notification$: ReplaySubject<any> = new ReplaySubject(1);
    private pushObject: PushObject;
    private channelImportance: Priority = 4;
    private androidChannels: Array<string> = [
        'default',
        'match'
    ];

    /**
     * Constructor
     */
    constructor(
        private persistentStorage: PersistentStorageService, 
        private application: ApplicationService,
        private nativeDevice: NativeDevice,
        private push: Push, 
        private http: SecureHttpService) {}

    /**
     * Watch notifications
     */
    watchNotifications(): ReplaySubject<any> {
        if (!this.isInit) {
            this.isInit = true;

            const appParams = this.application.getAppUrlParams();

            // disable push notices at all (needed for the acceptance tests)
            if (!appParams['disable_push']) {
                // create channels (Android O and above)
                if (!this.application.isAppRunningInExternalBrowser()) {
                    this.initAndroidChannels();
                }

                this.init();
            }
        }

        return this.notification$;
    }

    /**
     * Set android channels
     */
    setAndroidChannels(channels: Array<string>): void {
        this.androidChannels = channels;
    }

    /**
     * Init android channels
     */
    initAndroidChannels(): void {
        const channels: Array<Observable<any>> = [];

        this.androidChannels.forEach((channel: string) => channels.push(Observable.fromPromise(this.push.createChannel({
            id: channel,
            description: channel,
            importance: this.channelImportance,
            sound: channel
        }))));

        Observable.forkJoin(channels).subscribe();
    }

    /**
     * Init
     */
    init(): void {
        try {
            const options: PushOptions = {
                android: {
                    icon: 'notification'
                },
                ios: {
                    alert: 'true',
                    badge: 'false',
                    sound: 'true'
                },
                browser: {}
            };

            this.pushObject = this.push.init(options);

            this.pushObject.on('registration').subscribe(registration => this.registerDevice(registration));
            this.pushObject.on('notification').subscribe(notification => this.notification$.next(notification));
        }
        catch(error) {}
    }

    /**
     * Register device
     */
    registerDevice(registration: {registrationId: string}): boolean {
        const pushToken: string = this.persistentStorage.getValue('push_token');

        // prevent to register same tokens
        if (!pushToken || registration.registrationId !== pushToken) {
            const deviceData = {
                deviceUuid: this.application.getAppUuid(),
                token: registration.registrationId,
                platform: this.nativeDevice.platform,
                language: this.application.getLanguage()
            };

            this.http.post('/devices', deviceData).subscribe(() => {
                this.persistentStorage.setValue('push_token', registration.registrationId);
            });

            return true;
        }

        return false;
    }
}
