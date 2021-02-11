import { Component, ChangeDetectorRef, ChangeDetectionStrategy }  from '@angular/core';
import {ToastController, NavParams, NavController, ModalController, AlertController} from 'ionic-angular';
import { ISubscription } from 'rxjs/Subscription';
import { TranslateService } from 'ng2-translate';

// services
import { SiteConfigsService } from 'services/site-configs';
import { UserService } from 'services/user';
import { AuthService } from 'services/auth';

// pages
import { DashboardPage } from 'pages/dashboard';

// shared components
import {IFileUploadResult} from "shared/components/file-uploader";

@Component({
    selector: 'verify-photo',
    templateUrl: 'index.html',
    changeDetection: ChangeDetectionStrategy.OnPush,
})

export class VerifyPhotoPage {

    isPageLoading: boolean = false;
    verifyPhotoUrl: string = null;
    isPhotoUploadIng: boolean = false;
    photoUploadUri = '/verify-photo';
    declineText: string = '';

    private isPhotoUploaded: boolean = false;
    private photoKey: string = null;

    /**
     * Constructor
     */
    constructor(
        public siteConfigs: SiteConfigsService,
        protected translate: TranslateService,
        protected toast: ToastController,
        private alert: AlertController,
        private auth: AuthService,
        private user: UserService,
        private ref: ChangeDetectorRef,
        private modal: ModalController,
        private nav: NavController,
        private navParams: NavParams)
    {
        this.declineText = this.navParams.get('disapprovedText')
    }

    /**
     * Get avatar mime types
     */
    get getAvatarMimeTypes(): Array<string> {
        return this.siteConfigs.getConfig('validImageMimeTypes');
    }

    /**
     * Get avatar max size
     */
    get getAvatarMaxSize(): Array<string> {
        return this.siteConfigs.getConfig('avatarMaxUploadSize');
    }


    /**
     * Is photo valid
     */
    get isPhotoValid(): boolean {
        return this.isPhotoUploaded && !this.isPhotoUploadIng;
    }

    /**
     * Start uploading phpto callback
     */
    startUploadingPhotoCallback(): void {
        this.isPhotoUploadIng = true;
        this.ref.markForCheck();
    }

    /**
     * Success photo upload callback
     */
    successPhotoUploadCallback(response: IFileUploadResult): void {
        this.verifyPhotoUrl = response.data.url;
        this.photoKey = response.data.key;

        this.isPhotoUploaded = true;
        this.isPhotoUploadIng = false;
        this.ref.markForCheck();
    }

    /**
     * Error photo upload callback
     */
    errorPhotoUploadCallback(): void {
        this.isPhotoUploadIng = false;
        this.ref.markForCheck();

        const alert = this.alert.create({
            title: this.translate.instant('error_occurred'),
            subTitle: this.translate.instant('error_uploading_file'),
            buttons: [this.translate.instant('ok')]
        });

        alert.present();
    }

    /**
     * Submit form
     */
    submit(): void {
        if (!this.isPhotoValid) {
            this.showNotification('verify_photo_input_error');

            return
        }

        this.isPageLoading = true;
        this.ref.markForCheck();

        this.user.sendVerifyPhoto(this.photoKey).subscribe( () => {
            this.nav.setRoot(DashboardPage);
        })
    }

    /**
     * Show notification
     */
    protected showNotification(lang: string): void {
        const notificationToaster = this.toast.create({
            message: this.translate.instant(lang),
            closeButtonText: this.translate.instant('ok'),
            showCloseButton: true,
            duration: this.siteConfigs.getConfig('toastDuration')
        });

        notificationToaster.present();
    }
}
