import { Component, ChangeDetectionStrategy, ChangeDetectorRef, OnInit, Output, EventEmitter } from '@angular/core';
import { NavController, ToastController, AlertController } from 'ionic-angular';
import { TranslateService } from 'ng2-translate';

// services
import { PaymentsService, ICreditPackResponse } from 'services/payments';
import { SiteConfigsService } from 'services/site-configs';
import { UserService } from 'services/user';

// pages
import { DashboardPage } from 'pages/dashboard';

// base view membership page
import { BaseCreditsComponent } from '../base.credits';

@Component({
    selector: 'inapp-credits',
    templateUrl: 'index.html',
    changeDetection: ChangeDetectionStrategy.OnPush
})

export class InappCreditsComponent extends BaseCreditsComponent implements OnInit {
    @Output() packetBuying = new EventEmitter<any>();
    @Output() packetBuyingCancelled = new EventEmitter<any>();

    /**
     * Constructor
     */
    constructor(
        protected alert: AlertController,
        protected nav: NavController,
        protected toast: ToastController,
        protected translate: TranslateService, 
        protected siteConfigs: SiteConfigsService,
        private user: UserService,
        private payments: PaymentsService, 
        private ref: ChangeDetectorRef) 
    {
        super(
            alert,
            nav,
            toast,
            translate,
            siteConfigs
        );
    }

    /**
     * Component init
     */
    ngOnInit(): void {
        // load credits packs
        this.payments.loadCreditPacks().subscribe(response => {
            // process packs
            if (response.packs && response.packs.length) {
                // get only registered packs from the apple or google store
                this.payments.getRegisteredInAppProducts(response.packs).subscribe(registeredProducts => {
                    // synchronize received packs with registered ones
                    // we should show only existing packs  
                    response.packs = this.payments
                        .synchronizeItemsWithInAppProducts(response.packs, registeredProducts);

                    this.creditPacks = response.packs;
                    this.myBalance = response.balance;
                    this.isInfoAvailable = response.isInfoAvailable;
 
                    this.isPageLoading = false;
                    this.ref.markForCheck();
                });

                return;
            }

            this.creditPacks = response.packs;
            this.myBalance = response.balance;
            this.isPageLoading = false;
            this.ref.markForCheck();
        });
    }

    /**
     * Buy pack
     */
    buyPack(pack: ICreditPackResponse): void {
        this.buyingPackId = pack.id;
        this.ref.markForCheck();
        this.packetBuying.emit();

        this.payments.purchaseInappProduct(pack.definedProductId, pack.productId, false).subscribe(purchaseData => {
            if (purchaseData) {
                // update logged user's data (including permissions)
                this.user.loadMe().subscribe(() => {
                    this.showNotification('credits_updated', {
                        count: pack.credits
                    });

                    this.nav.setRoot(DashboardPage);
                });

                return;
            }

            this.buyingPackId = 0;
            this.ref.markForCheck();
            this.showNotification('purchase_cancelled');
            this.packetBuyingCancelled.emit();
        });
    }
}
