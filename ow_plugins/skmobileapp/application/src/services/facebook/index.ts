import { Injectable } from '@angular/core';
import { Observable } from 'rxjs/Observable';
import { Facebook, FacebookLoginResponse } from '@ionic-native/facebook'
import { Subject } from 'rxjs/Subject';

// services
import { SecureHttpService } from 'services/http';
import { ApplicationService } from 'services/application';

// responses
import { ILoginResponse } from './responses';


@Injectable()
export class FacebookService {
    private permissions: Array<string> = [
        'public_profile',
        'email'
    ];

    /**
     * Constructor
     */
    constructor (
        private http: SecureHttpService,
        private application: ApplicationService,
        private facebook: Facebook) {}

    /**
     * Login
     */
    loadFacebookCredentials(): Observable<FacebookLoginResponse> {
        return Observable.fromPromise(this.facebook.login(this.permissions));
    }

    /**
     * Login
     */
    login(facebookResponse: FacebookLoginResponse): Observable<ILoginResponse> {
        return this.http.post('/facebook-connect', facebookResponse);
    }

    /**
     * Get manual login url
     */
    getManualLoginUrl(): string {
        const redirectUrlArr = this.application.getAppUrl().split('#');
        const redirectUrl = encodeURIComponent(redirectUrlArr[0]);

        return `https://www.facebook.com/v3.2/dialog/oauth?client_id=${this.application.getConfig('facebookAppId')}&response_type=token,granted_scopes&redirect_uri=${redirectUrl}`;
    }

    /**
     * Load facebook credentials by token
     */
    loadFacebookCredentialsByToken(accessToken: string): Observable<FacebookLoginResponse> {
        const credentials$: Subject<FacebookLoginResponse> = new Subject();

        Observable
            .fromPromise(this.facebook.api('/me?fields=id&access_token=' + accessToken, this.permissions))
            .subscribe(data => {
                credentials$.next({
                    status: 'connected',
                    authResponse: {
                        session_key: false,
                        accessToken: accessToken,
                        expiresIn: 0,
                        sig: '',
                        secret: '',
                        userID: data.id
                    }
                });
            });

        return credentials$;
    }
}