import { Component, Input, OnInit, OnDestroy, ChangeDetectorRef, ChangeDetectionStrategy }  from '@angular/core';
import { FormGroup } from '@angular/forms';
import {ToastController, NavParams, NavController, ModalController, AlertController} from 'ionic-angular';
import { ISubscription } from 'rxjs/Subscription';
import { TranslateService } from 'ng2-translate';

// services
import { SiteConfigsService } from 'services/site-configs';
import { UserService, IUserData } from 'services/user';
import { AuthService } from 'services/auth';

// pages
import { JoinInitialPage } from 'pages/user/join/initial';
import { DashboardPage } from 'pages/dashboard';
import { BaseFormBasedPage } from 'pages/base.form.based'

// questions
import { QuestionBase } from 'services/questions/questions/base';
import { QuestionManager } from 'services/questions/manager';
import { QuestionControlService } from 'services/questions/control.service';

// shared components
import { CustomPageComponent } from 'shared/components/custom-page';
import {IFileUploadResult} from "shared/components/file-uploader";

@Component({
    selector: 'join-questions',
    templateUrl: 'index.html',
    changeDetection: ChangeDetectionStrategy.OnPush,
    providers: [
        QuestionControlService,
        QuestionManager
    ]
})

export class JoinQuestionsPage extends BaseFormBasedPage implements OnInit, OnDestroy {
    @Input() questions: Array<QuestionBase> = []; // list of questions

    isPageLoading: boolean = false;
    isUserCreating: boolean = false;
    form: FormGroup;
    sections: any = [];
    tosValue: boolean = false;

    verifyPhotoUrl: string = null;
    isPhotoUploadIng: boolean = false;
    photoUploadUri = '/verify-photo';

    private initialData: IUserData;
    private currentGender: number;
    private siteConfigsSubscription: ISubscription;

    private isPhotoUploaded: boolean = false;
    private photoKey: string = null;

    /**
     * Constructor
     */
    constructor(
        public questionControl: QuestionControlService,
        public siteConfigs: SiteConfigsService,
        protected toast: ToastController,
        protected translate: TranslateService,
        private alert: AlertController,
        private auth: AuthService,
        private user: UserService,
        private ref: ChangeDetectorRef,
        private modal: ModalController,
        private nav: NavController,
        private navParams: NavParams,
        private questionManager: QuestionManager)
    {
        super(
            questionControl, 
            siteConfigs,
            translate,
            toast
        );

        this.initialData = this.navParams.get('initial');
        this.currentGender = this.initialData.sex;
    }

    /**
     * Component init
     */
    ngOnInit(): void {
        // watch configs changes
        this.siteConfigsSubscription = this.siteConfigs.watchConfigGroup([
            'isAvatarRequired',
            'isTosActive'
        ]).subscribe(configs => {
            let [isAvatarRequired, isTosActive] = configs;

            // avatar is required but not uploaded
            if (isAvatarRequired === true && !this.initialData.avatarKey) {
                this.nav.popTo(JoinInitialPage);

                return;
            }

            // reset the tos value
            if (isTosActive === false) {
                this.tosValue = false;
            }

            // refresh view
            this.ref.markForCheck();
        });

        this.isPageLoading = true;
        this.ref.markForCheck();

        // load questions
        this.user.loadJoinQuestions(this.currentGender).subscribe(response => {
            // process questions
            response.questions.forEach(questionData => {
                const data = {
                    section: '',
                    questions: []
                };

                data.section = questionData.section;

                questionData.items.forEach(question => {
                    const questionItem:QuestionBase = this.questionManager.getQuestion(question.type, {
                        key: question.key,
                        label: question.label,
                        placeholder: question.placeholder,
                        values: question.values,
                        value: question.value
                    }, question.params);

                    // add validators
                    if (question.validators) {
                        questionItem.validators = question.validators;
                    }

                    data.questions.push(questionItem);
                    this.questions.push(questionItem);
                });

                this.sections.push(data);
            });

            // register all questions inside a form group
            this.form = this.questionControl.toFormGroup(this.questions);

            this.isPageLoading = false;
            this.ref.markForCheck();
        });
    }

    /**
     * Component destroy
     */
    ngOnDestroy(): void {
        this.siteConfigsSubscription.unsubscribe();
    }

    /**
     * Is TOS active
     */
    get isTosActive(): boolean {
        return this.siteConfigs.getConfig('isTosActive');
    }

    /**
     * Is tos valid
     */
    get isTosValid(): boolean {
        return this.isTosActive && this.tosValue || !this.isTosActive;
    }

    /**
     * Show tos modal
     */
    showTosModal(): void {
        const modal = this.modal.create(CustomPageComponent, {
            title: this.translate.instant('tos_page_header'),
            pageName: 'tos_page_content'
        });

        modal.present();
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
        // is form valid
        if (this.siteConfigs.isPluginActive('photver') && !this.isPhotoValid) {
            this.showNotification('verify_photo_input_error');

            return;
        }

        if (!this.form.valid || !this.isTosValid) {
            // tos is not selected
            if (this.form.valid && !this.isTosValid) {
                this.showNotification('tos_agree_input_error');

                return;
            }

            this.showFormGeneralError(this.form);

            return;
        }

        this.isUserCreating = true;
        this.ref.markForCheck();

        const userData: IUserData = {
            userName: this.initialData.userName,
            email: this.initialData.email,
            password: this.initialData.password,
            sex: this.initialData.sex,
            avatarKey: this.initialData.avatarKey
        }

        this.user.createMe(userData).subscribe(response => {
            // set user authenticated
            this.auth.setAuthenticated(response.token);

            // create questions
            const processedQuestions = [];
            this.questions.forEach(questionData => {
                processedQuestions.push({
                    name: questionData.key,
                    value: this.form.value[questionData.key],
                    type: questionData.controlType
                });
            });

            // add match sex
            processedQuestions.push({
                name: 'match_sex',
                value: this.initialData.lookingFor,
                type: QuestionManager.TYPE_MULTICHECKBOX
            });

            if (this.siteConfigs.isPluginActive('photver')) {
                // add verify photo key
                processedQuestions.push({
                    name: 'verify_photo_key',
                    value: this.photoKey,
                });
            }

            this.user.createQuestionsData(processedQuestions).subscribe(() => {
                this.nav.setRoot(DashboardPage);
            });
        });
    }
}
