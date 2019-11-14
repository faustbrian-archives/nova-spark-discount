<template>
    <div class="mb-8">
        <div class="flex items-center mb-3">
            <h1 class="flex-no-shrink text-90 font-normal text-2xl">Stripe Discount</h1>
        </div>

        <!-- Add Discount Form -->
        <form role="form" class="spaced-y-4" @submit.prevent="applyDiscount">
            <div class="card mb-6 py-3 px-6">
                <!-- Current Discount -->
                <div
                    class="px-4 py-2 bg-info text-white rounded inline-block"
                    v-if="currentDiscount"
                >
                    <span
                        v-if="currentDiscount.duration=='repeating' && currentDiscount.duration_in_months > 1"
                    >This {{ this.entityName }} has a discount of {{ formattedDiscount(currentDiscount) }} for all invoices during the next {{ currentDiscount.duration_in_months }} months.</span>

                    <span
                        v-if="currentDiscount.duration=='repeating' && currentDiscount.duration_in_months == 1"
                    >This {{ this.entityName }} has a discount of {{ formattedDiscount(currentDiscount) }} for all invoices during the next month.</span>

                    <span
                        v-if="currentDiscount.duration=='forever'"
                    >This {{ this.entityName }} has a discount of {{ formattedDiscount(currentDiscount) }} forever.</span>

                    <span
                        v-if="currentDiscount.duration=='once'"
                    >This {{ this.entityName }} has a discount of {{ formattedDiscount(currentDiscount) }} for a single invoice.</span>
                </div>

                <!-- Discount Type -->
                <div class="flex border-b border-40">
                    <div class="w-1/5 px-8 py-6">
                        <label for="type" class="inline-block text-80 pt-2 leading-tight">
                            Type
                            <span class="text-danger text-sm">*</span>
                        </label>
                    </div>
                    <div class="py-6 px-8 w-1/2">
                        <select
                            class="form-control form-select mb-3 w-full"
                            :class="{ 'border-danger': formErrors.type }"
                            v-model="form.type"
                        >
                            <option value="amount">Amount</option>
                            <option value="percent">Percentage</option>
                        </select>

                        <div
                            class="help-text mt-2 text-danger"
                            v-if="formErrors.type"
                        >{{ formErrors.type[0] }}</div>
                    </div>
                </div>

                <!-- Discount Value -->
                <div class="flex border-b border-40">
                    <div class="w-1/5 px-8 py-6">
                        <label for="value" class="inline-block text-80 pt-2 leading-tight">
                            Percent / Amount
                            <span class="text-danger text-sm">*</span>
                        </label>
                    </div>
                    <div class="py-6 px-8 w-1/2">
                        <input
                            type="number"
                            class="w-full form-control form-input form-input-bordered"
                            :class="{ 'border-danger': formErrors.value }"
                            v-model="form.value"
                        />

                        <div
                            class="help-text mt-2 text-danger"
                            v-if="formErrors.value"
                        >{{ formErrors.value[0] }}</div>
                    </div>
                </div>

                <!-- Discount Duration -->
                <div
                    class="flex border-b border-40"
                    :class="{ 'remove-bottom-border': form.duration !== 'repeating' }"
                >
                    <div class="w-1/5 px-8 py-6">
                        <label for="duration" class="inline-block text-80 pt-2 leading-tight">
                            Duration
                            <span class="text-danger text-sm">*</span>
                        </label>
                    </div>
                    <div class="py-6 px-8 w-1/2">
                        <select
                            class="form-control form-select mb-3 w-full"
                            :class="{ 'border-danger': formErrors.duration }"
                            v-model="form.duration"
                        >
                            <option value="once">Once</option>
                            <option value="forever">Forever</option>
                            <option value="repeating">Multiple Months</option>
                        </select>

                        <div
                            class="help-text mt-2 text-danger"
                            v-if="formErrors.duration"
                        >{{ formErrors.duration[0] }}</div>
                    </div>
                </div>

                <!-- Duration Months -->
                <div
                    class="flex border-b border-40 remove-bottom-border"
                    v-if="form.duration === 'repeating'"
                >
                    <div class="w-1/5 px-8 py-6">
                        <label for="months" class="inline-block text-80 pt-2 leading-tight">
                            Months
                            <span class="text-danger text-sm">*</span>
                        </label>
                    </div>

                    <div class="py-6 px-8 w-1/2">
                        <input
                            type="number"
                            class="w-full form-control form-input form-input-bordered"
                            :class="{ 'border-danger': formErrors.months }"
                            v-model="form.months"
                        />

                        <div
                            class="help-text mt-2 text-danger"
                            v-if="formErrors.months"
                        >{{ formErrors.months[0] }}</div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center">
                <button
                    type="submit"
                    class="btn btn-default btn-primary inline-flex items-center relative"
                >
                    <span class>Apply Discount</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import Dinero from "dinero.js";

export default {
    props: ["resourceName", "resourceId", "field"],

    data() {
        return {
            currentDiscount: null,

            form: {
                type: "amount",
                value: null,
                duration: "once",
                months: null
            },
            formErrors: {}
        };
    },

    computed: {
        entityName() {
            return this.resourceName.slice(0, -1);
        }
    },

    mounted() {
        this.getCurrentDiscountForBillable();
    },

    methods: {
        /**
         * Get the current discount for the given billable entity.
         */
        getCurrentDiscountForBillable() {
            this.currentDiscount = null;

            Nova.request()
                .get(
                    `/nova-vendor/nova-spark-discount/${this.resourceName}/${this.resourceId}`
                )
                .then(response => {
                    if (response.status === 200) {
                        this.currentDiscount = response.data;
                    }
                });
        },

        /**
         * Get the formatted discount amount for the given discount.
         */
        formattedDiscount(discount) {
            if (!discount) {
                return;
            }

            if (discount.percent_off) {
                return `${discount.percent_off}%`;
            }

            return Dinero({
                amount: this.calculateAmountOff(discount.amount_off),
                currency: "EUR"
            })
                .setLocale("fi")
                .toFormat("$0,0.00");
        },

        /**
         * Calculate the amount off for the given discount amount.
         */
        calculateAmountOff(amount) {
            return amount / 100;
        },

        /**
         * Apply the discount to the user.
         */
        applyDiscount() {
            Nova.request()
                .post(
                    `/nova-vendor/nova-spark-discount/${this.resourceName}/${this.resourceId}`,
                    this.form
                )
                .then(response => {
                    this.$toasted.show("The discount has been applied.", {
                        type: "success"
                    });

                    this.getCurrentDiscountForBillable();
                })
                .catch(e => {
                    if (e.response.data.errors) {
                        this.$toasted.show(
                            "There was a problem submitting the form.",
                            { type: "error" }
                        );

                        this.formErrors = e.response.data.errors || {};
                    } else {
                        this.$toasted.show(e.response.data.message, {
                            type: "error"
                        });
                    }
                });
        }
    }
};
</script>
