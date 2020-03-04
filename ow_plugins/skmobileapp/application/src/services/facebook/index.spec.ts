import { Observable } from 'rxjs/Rx';
import { MockBackend } from '@angular/http/testing';
import { Http, BaseRequestOptions } from '@angular/http';
import { TestBed } from '@angular/core/testing';
import { Facebook, FacebookLoginResponse } from '@ionic-native/facebook'
import { Platform } from 'ionic-angular';

// services
import { FacebookService } from './'
import { SecureHttpService } from 'services/http';
import { ApplicationService } from 'services/application';
import { PersistentStorageService } from 'services/persistent-storage';
import { JwtService } from 'services/jwt';
import { AuthService } from 'services/auth';

// fakes
import { PlatformMock } from 'ionic-mocks';

import {
    AuthServiceFake,
    JwtFake,
    ReduxFake, 
    ApplicationServiceFake, 
    ApplicationConfigFake,
    StringUtilsFake,
    DeviceFake,
    PersistentStorageMemoryAdapterFake 
} from 'test/fake';


import { ILoginResponse } from './responses';  

describe('Facebook service', () => {
    // register service's fakes
    let fakeHttp: SecureHttpService;
    let fakeFacebookNative: Facebook;
    let fakeApplication: ApplicationService;

    let facebook: FacebookService; // testable service

    beforeEach(() => {
        TestBed.configureTestingModule({
            providers: [{
                    provide: ApplicationService,
                    useFactory: (fakeStorage, fakePlatform) => new ApplicationServiceFake(ApplicationConfigFake, new ReduxFake(), fakeStorage, new DeviceFake, new StringUtilsFake, fakePlatform),
                    deps: [PersistentStorageService, Platform]
                }, {
                    provide: PersistentStorageService,
                    useFactory: () => new PersistentStorageService(new PersistentStorageMemoryAdapterFake),
                    deps: []
                }, {
                    provide: JwtService,
                    useFactory: () => new JwtFake(),
                    deps: []
                }, {
                    provide: AuthService,
                    useFactory: (fakeStorage, fakeJwt) => new AuthServiceFake(fakeStorage, fakeJwt),
                    deps: [PersistentStorageService, JwtService]
                }, {
                    provide: SecureHttpService,
                    useFactory: (fakeApplication, fakeHttp, fakeAuth, fakePersistentStorage) => new SecureHttpService(fakePersistentStorage, fakeApplication, fakeHttp, fakeAuth),
                    deps: [ApplicationService, Http, AuthService, PersistentStorageService]
                }, {
                    provide: Http, 
                    useFactory: () => new Http(new MockBackend, new BaseRequestOptions), 
                    deps: [] 
                }, {
                    provide: Platform, 
                    useFactory: () => PlatformMock.instance(), 
                    deps: [] 
                },
                FacebookService,
                Facebook
            ]}
        );

        // init service's fakes
        fakeHttp = TestBed.get(SecureHttpService);
        fakeFacebookNative = TestBed.get(Facebook);
        fakeApplication = TestBed.get(ApplicationService);

        // init service
        facebook = TestBed.get(FacebookService)
    });

    it('loadFacebookCredentials should return correct result', () => {
        const facebookResponse: FacebookLoginResponse = {
            status: 'test',
            authResponse: {
                session_key: false,
                accessToken: 'test',
                expiresIn: 1,
                sig: 'test',
                secret: 'test',
                userID: 'test'
            }
        };

        // fake native facebook
        spyOn(fakeFacebookNative, 'login').and.returnValue(
            Promise.resolve(facebookResponse)
        );

        facebook.loadFacebookCredentials().subscribe(data => {
            expect(fakeFacebookNative.login).toHaveBeenCalledWith([
                'public_profile', 
                'email'
            ]);

            expect(data).toEqual(facebookResponse);
        });
    });

    it('login should return correct result', () => {
        const facebookResponse: FacebookLoginResponse = {
            status: 'test',
            authResponse: {
                session_key: false,
                accessToken: 'test',
                expiresIn: 1,
                sig: 'test',
                secret: 'test',
                userID: 'test'
            }
        };

        const response: ILoginResponse = {
            isSuccess: true
        };

        // fake http
        spyOn(fakeHttp, 'post').and.returnValue(
            Observable.of(response)
        );

        facebook.login(facebookResponse).subscribe(data => {
            expect(fakeHttp.post).toHaveBeenCalledWith('/facebook-connect', facebookResponse);
            expect(data).toEqual(response);
        });
    });

    it('getManualLoginUrl should return correct result', () => {
        const url: string = 'http://test.com?pwa=1#access_token=12&hash2=13';
        const facebookAppId: string = '67676767';
        const expectedUrl: string = encodeURIComponent('http://test.com?pwa=1');

        spyOn(fakeApplication, 'getAppUrl').and.returnValue(url);
        spyOn(fakeApplication, 'getConfig').and.returnValue(facebookAppId);

        expect(facebook.getManualLoginUrl()).toEqual(`https://www.facebook.com/v3.2/dialog/oauth?client_id=${facebookAppId}&response_type=token,granted_scopes&redirect_uri=${expectedUrl}`);
    });

    it('loadFacebookCredentialsByToken should return correct result', () => {
        const id: number = 123456789;
        const token: string = 'test';

        const permissions: Array<string> = [
            'public_profile',
            'email'
        ];

        spyOn(fakeFacebookNative, 'api').and.returnValue(
            Promise.resolve({
                id: id
            })
        );

        facebook.loadFacebookCredentialsByToken(token).subscribe(data => {
            expect(data).toEqual({
                status: 'connected',
                authResponse: {
                    session_key: false,
                    accessToken: token,
                    expiresIn: 0,
                    sig: '',
                    secret: '',
                    userID: id
                }
            });
            expect(fakeFacebookNative.api).toHaveBeenCalledWith('/me?fields=id&access_token=' + token, permissions);
        });
    });
});