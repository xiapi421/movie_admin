<template>
    <!-- 对话框表单 -->
    <!-- 建议使用 Prettier 格式化代码 -->
    <!-- el-form 内可以混用 el-form-item、FormItem、ba-input 等输入组件 -->
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
        width="50%"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']" v-zoom="'.ba-operate-dialog'">
                {{ baTable.form.operate ? t(baTable.form.operate) : '' }}
            </div>
        </template>
        <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
            <div
                class="ba-operate-form"
                :class="'ba-' + baTable.form.operate + '-form'"
                :style="config.layout.shrink ? '':'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
            >
                <el-form
                    v-if="!baTable.form.loading"
                    ref="formRef"
                    @submit.prevent=""
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    :label-position="config.layout.shrink ? 'top' : 'right'"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                >
                    <!-- <FormItem :label="t('user.user.group_id')" type="remoteSelect" v-model="baTable.form.items!.group_id" prop="group_id" :input-attr="{ pk: 'id', field: 'name', remoteUrl: '' }" :placeholder="t('Please select field', { field: t('user.user.group_id') })" /> -->
                    <FormItem :label="t('user.user.username')" type="string" v-model="baTable.form.items!.username" prop="username" :placeholder="t('Please input field', { field: t('user.user.username') })" />
                    <!-- <FormItem :label="t('user.user.money')" type="number" v-model="baTable.form.items!.money" prop="money" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('user.user.money') })" /> -->
                    <!-- <FormItem :label="t('user.user.score')" type="number" v-model="baTable.form.items!.score" prop="score" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('user.user.score') })" /> -->
                    <FormItem label="登录地址" type="string" v-model="baTable.form.items!.login_url" prop="login_url" :input-attr="{disabled:true}" />    
                    <FormItem :label="t('user.user.password')" type="string" v-model="newPassword" prop="password" placeholder="不修改密码则留空" />
                    <FormItem label='提现密码' type="string" v-model="baTable.form.items!.txPassword" prop="txPassword" :placeholder="t('Please input field', { field: t('user.user.txPassword') })" />
                    <!-- <FormItem :label="t('user.user.status')" type="radio" v-model="baTable.form.items!.status" prop="status" :input-attr="{ content: { '0': t('user.user.status 0'), '1': t('user.user.status 1') } }" :placeholder="t('Please select field', { field: t('user.user.status') })" /> -->
                    <!-- <FormItem :label="t('user.user.invite_code')" type="string" v-model="baTable.form.items!.invite_code" prop="invite_code" :placeholder="t('Please input field', { field: t('user.user.invite_code') })" />
                    <FormItem :label="t('user.user.share_status')" type="radio" v-model="baTable.form.items!.share_status" prop="share_status" :input-attr="{ content: { '0': t('user.user.share_status 0'), '1': t('user.user.share_status 1') } }" :placeholder="t('Please select field', { field: t('user.user.share_status') })" />
                    <FormItem :label="t('user.user.pay_status')" type="radio" v-model="baTable.form.items!.pay_status" prop="pay_status" :input-attr="{ content: { '0': t('user.user.pay_status 0'), '1': t('user.user.pay_status 1') } }" :placeholder="t('Please select field', { field: t('user.user.pay_status') })" />
                    <FormItem :label="t('user.user.withdraw_status')" type="radio" v-model="baTable.form.items!.withdraw_status" prop="withdraw_status" :input-attr="{ content: { '0': t('user.user.withdraw_status 0'), '1': t('user.user.withdraw_status 1') } }" :placeholder="t('Please select field', { field: t('user.user.withdraw_status') })" />
                    <FormItem :label="t('user.user.lastday_sell')" type="number" v-model="baTable.form.items!.lastday_sell" prop="lastday_sell" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('user.user.lastday_sell') })" />
                    <FormItem :label="t('user.user.today_sell')" type="number" v-model="baTable.form.items!.today_sell" prop="today_sell" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('user.user.today_sell') })" />
                    <FormItem :label="t('user.user.lastday_money')" type="number" v-model="baTable.form.items!.lastday_money" prop="lastday_money" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('user.user.lastday_money') })" />
                    <FormItem :label="t('user.user.single_price')" type="number" v-model="baTable.form.items!.single_price" prop="single_price" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('user.user.single_price') })" />
                    <FormItem :label="t('user.user.day_price')" type="number" v-model="baTable.form.items!.day_price" prop="day_price" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('user.user.day_price') })" />
                    <FormItem :label="t('user.user.week_price')" type="number" v-model="baTable.form.items!.week_price" prop="week_price" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('user.user.week_price') })" />
                    <FormItem :label="t('user.user.month_price')" type="number" v-model="baTable.form.items!.month_price" prop="month_price" :input-attr="{ step: 1 }" :placeholder="t('Please input field', { field: t('user.user.month_price') })" />
                    <FormItem :label="t('user.user.theme_id')" type="remoteSelect" v-model="baTable.form.items!.theme_id" prop="theme_id" :input-attr="{ pk: 'id', field: 'name', remoteUrl: '' }" :placeholder="t('Please select field', { field: t('user.user.theme_id') })" /> -->
                </el-form>
            </div>
        </el-scrollbar>
        <template #footer>
            <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                <el-button @click="baTable.toggleForm()">{{ t('Cancel') }}</el-button>
                <el-button v-blur :loading="baTable.form.submitLoading" @click="baTable.onSubmit(formRef)" type="primary">
                    {{ baTable.form.operateIds && baTable.form.operateIds.length > 1 ? t('Save and edit next item') : t('Save') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import type { FormInstance, FormItemRule } from 'element-plus'
import { inject, reactive, ref, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'  
import FormItem from '/@/components/formItem/index.vue'
import { useConfig } from '/@/stores/config'
import type baTableClass from '/@/utils/baTable'
import { buildValidatorData } from '/@/utils/validate'

const config = useConfig()
const formRef = ref<FormInstance>()
const baTable = inject('baTable') as baTableClass
const newPassword = ref('')
const { t } = useI18n()
watch(newPassword, (val: string | undefined) => {
    if (val) {          
        baTable.form.items!.password = val
    }
})
const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    // money: [buildValidatorData({ name: 'number', title: t('user.user.money') })],
    // score: [buildValidatorData({ name: 'number', title: t('user.user.score') })],
    // update_time: [buildValidatorData({ name: 'date', title: t('user.user.update_time') })],
    // create_time: [buildValidatorData({ name: 'date', title: t('user.user.create_time') })],
    // lastday_sell: [buildValidatorData({ name: 'number', title: t('user.user.lastday_sell') })],
    // today_sell: [buildValidatorData({ name: 'number', title: t('user.user.today_sell') })],
    // lastday_money: [buildValidatorData({ name: 'number', title: t('user.user.lastday_money') })],
    // single_price: [buildValidatorData({ name: 'number', title: t('user.user.single_price') })],
    // day_price: [buildValidatorData({ name: 'number', title: t('user.user.day_price') })],
    // week_price: [buildValidatorData({ name: 'number', title: t('user.user.week_price') })],
    // month_price: [buildValidatorData({ name: 'number', title: t('user.user.month_price') })],
})
onMounted(() => {
    newPassword.value = ''
})
</script>

<style scoped lang="scss"></style>
