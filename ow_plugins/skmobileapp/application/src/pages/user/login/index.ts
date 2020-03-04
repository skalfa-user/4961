import { Component, Input, ChangeDetectionStrategy, OnInit, OnDestroy, ChangeDetectorRef } from '@angular/core';
import { FormGroup } from '@angular/forms';
import { ToastController, AlertController, NavController } from 'ionic-angular';
import { TranslateService } from 'ng2-translate';
import { ISubscription } from 'rxjs/Subscription';

// service
import { UserService } from 'services/user';
import { ApplicationService } from 'services/application';
import { AuthService } from 'services/auth';
import { SiteConfigsService } from 'services/site-configs';
import { FacebookService } from 'services/facebook';
import { PersistentStorageService } from 'services/persistent-storage';

// questions
import { QuestionBase } from 'services/questions/questions/base';
import { QuestionManager } from 'services/questions/manager';
import { QuestionControlService } from 'services/questions/control.service';

// pages
import { AppUrlPage } from 'pages/app-url';
import { DashboardPage } from 'pages/dashboard';
import { JoinInitialPage } from 'pages/user/join/initial';
import { ForgotPasswordCheckEmailPage } from 'pages/user/forgot-password/check-email';

@Component({
    selector: 'login',
    templateUrl: 'index.html',
    changeDetection: ChangeDetectionStrategy.OnPush,
    providers: [
        QuestionControlService,
        QuestionManager,
        FacebookService
    ]
})

export class LoginPage implements OnInit, OnDestroy {
    @Input() questions: Array<QuestionBase> = []; // list of questions

    isFacebookInProcess: boolean = false;
    form: FormGroup;
    loginInProcessing: boolean = false;  
    forgotPasswordPage = ForgotPasswordCheckEmailPage;
    joinPage = JoinInitialPage;
    appUrlPage = AppUrlPage;

    private siteConfigsSubscription: ISubscription;

    /**
     * Constructor
     */
    constructor(
        public questionControl: QuestionControlService,
        public siteConfigs: SiteConfigsService,
        public translate: TranslateService,
        public toast: ToastController,
        private ref: ChangeDetectorRef,
        private user: UserService,
        private auth: AuthService,
        private application: ApplicationService,
        private nav: NavController,
        private alert: AlertController,
        private facebook: FacebookService,
        private persistentStorage: PersistentStorageService,
        private questionManager: QuestionManager) {}

    /**
     * Component init
     */
    ngOnInit(): void {
        // watch configs changes
        this.siteConfigsSubscription = this.siteConfigs
            .watchIsPluginActive('fbconnect')
            .subscribe(() => this.ref.markForCheck());

        // Android pwa hack facebook login
        if (this.application.isAppRunningInPwaMode() && this.persistentStorage.getValue('isFacebookPwaLogin')) {
            const urlParams = this.application.getAppUrlParams();

            if (urlParams['access_token']) {
                // trying to get facebook user credentials
                this.facebook.loadFacebookCredentialsByToken(
                    urlParams['access_token']).subscribe(credentials => this.facebookAuth(credentials));
            }
        }

        const isDemoModeActivated: boolean = this.siteConfigs.getConfig('isDemoModeActivated');
 
        // create form items
        this.questions = [
            this.questionManager.getQuestion(QuestionManager.TYPE_TEXT, {
                key: 'login',
                placeholder: this.translate.instant('login_input'),
                value: isDemoModeActivated ? 'demo' : '',
                validators: [
                    {name: 'require'}
                ]
            }, {
                questionClass: 'sk-name',
                hideWarning: true
            }),
            this.questionManager.getQuestion(QuestionManager.TYPE_PASSWORD, {
                key: 'password',
                placeholder: this.translate.instant('password_input'),
                value: isDemoModeActivated ? 'demo' : '',
                validators: [
                    {name: 'require'}
                ]
            }, {
                questionClass: 'sk-password',
                hideWarning: true
            })
        ];

        // register all questions inside a form group
        this.form = this.questionControl.toFormGroup(this.questions);
    }

    /**
     * Component destroy
     */
    ngOnDestroy(): void {
        this.siteConfigsSubscription.unsubscribe();
    }
 
    /**
     * Is facebook connect available
     */
    get isFacebookConnectAvailable(): boolean {
        return this.application.getConfig('facebookAppId') && this.siteConfigs.isPluginActive('fbconnect');
    }

    /**
     * Is generic site url
     */
    get isGenericSiteUrl(): boolean {
        if (this.application.getGenericApiUrl()) {
            return true;
        }

        return false;
    }
 
    /**
     * Login
     */
    login(): void {
        this.loginInProcessing = true;
        this.ref.markForCheck();

        this.user.login(this.form.value.login,
                this.form.value.password).subscribe(data => {

            this.loginInProcessing = false;
            this.ref.markForCheck();

            if (data.success === true) {
                this.auth.setAuthenticated(data.token);
                this.nav.setRoot(DashboardPage);

                return;
            }

            this.showAlert(this.translate.instant('login_failed'));
        });
    }

    /**
     * Facebook login
     */
    facebookLogin(): void {
        // Android pwa hack
        if (this.application.isAppRunningInPwaMode() && !this.application.isAppRunningInMobileSafari()) {
            window.open(this.facebook.getManualLoginUrl(), '_self');

            // Facebook login flag
            this.persistentStorage.setValue('isFacebookPwaLogin', 1);

            return;
        }

        // trying to get facebook user credentials
        this.facebook.loadFacebookCredentials().subscribe(credentials => this.facebookAuth(credentials));
    }

    /**
     * Facebook Auth
     */
    facebookAuth(credentials): void {
        this.isFacebookInProcess = true;
        this.ref.markForCheck();

        //  trying to make the user logged in
        this.facebook.login(credentials).subscribe(response => {
            // the user successfully logged in
            if (response && response.isSuccess) {
                this.auth.setAuthenticated(response.token);
                this.nav.setRoot(DashboardPage);

                return;
            }

            this.showAlert(this.translate.instant('error_occurred'));
            this.isFacebookInProcess = false;
            this.ref.markForCheck();
        });
    }

    /**
     * Show alert
     */
    private showAlert(message: string): void {
        const alert = this.alert.create({
            title: this.translate.instant('error_occurred'),
            subTitle: message,
            buttons: [this.translate.instant('ok')]
        });

        alert.present();
    }
}
