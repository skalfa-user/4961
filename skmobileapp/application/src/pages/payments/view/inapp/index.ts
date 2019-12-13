import { Component, OnInit, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { NavParams, NavController, ToastController } from 'ionic-angular';
import { TranslateService } from 'ng2-translate';

// services
import { PaymentsService, IMembershipPlanResponse } from 'services/payments';
import { SiteConfigsService } from 'services/site-configs';
import { UserService } from 'services/user';

// pages
import { DashboardPage } from 'pages/dashboard';

// base view membership page
import { BaseViewMembership } from '../base.view';

@Component({
    selector: 'view-membership-inapp',
    templateUrl: 'index.html',
    changeDetection: ChangeDetectionStrategy.OnPush
})

export class ViewMembershipInAppPage extends BaseViewMembership implements OnInit {
    /**
     * Constructor
     */
    constructor(
        protected translate: TranslateService,
        protected toast: ToastController,
        protected siteConfigs: SiteConfigsService,
        protected navParams: NavParams, 
        private user: UserService,
        private nav: NavController,
        private ref: ChangeDetectorRef,
        private payments: PaymentsService) 
    {
        super(
            translate,
            toast,
            siteConfigs,
            navParams
        );
    }

    /**
     * Component init
     */
    ngOnInit(): void {
        // load the membership
        this.payments.loadMembership(this.membershipId).subscribe(membership => {
            // process plans
            if (membership.plans && membership.plans.length) {
                // get only registered plans from the apple or google store
                this.payments.getRegisteredInAppProducts(membership.plans).subscribe(registeredProducts => {
                    // synchronize received plans with registered ones
                    // we should show only existing plans  
                    membership.plans = this.payments
                        .synchronizeItemsWithInAppProducts(membership.plans, registeredProducts);

                    this.membership = membership;
                    this.isPageLoading = false;
                    this.ref.markForCheck();
                });

                return;
            }

            this.membership = membership;
            this.isPageLoading = false;
            this.ref.markForCheck();
        });
    }

    /**
     * Buy product
     */
    buyProduct(plan: IMembershipPlanResponse): void {
        this.buyingPlanId = plan.id;
        this.ref.markForCheck();

        this.payments.purchaseInappProduct(plan.definedProductId, plan.productId, plan.isRecurring).subscribe((purchaseData) => {
            if (purchaseData) {
                // update logged user's data (including permissions)
                this.user.loadMe().subscribe(() => {
                    this.showNotification('membership_updated');
                    this.nav.setRoot(DashboardPage);
                });

                return;
            }

            this.buyingPlanId = 0;
            this.ref.markForCheck();
            this.showNotification('purchase_cancelled');
        });
    }
}
