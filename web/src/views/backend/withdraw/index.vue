<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <!-- 自定义按钮请使用插槽，甚至公共搜索也可以使用具名插槽渲染，参见文档 -->
        <TableHeader
            :buttons="['refresh', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('withdraw.quick Search Fields') })"
        ></TableHeader>

        <!-- 表格 -->
        <!-- 表格列有多种自定义渲染方式，比如自定义组件、具名插槽等，参见文档 -->
        <!-- 要使用 el-table 组件原有的属性，直接加在 Table 标签上即可 -->
        <Table ref="tableRef"></Table>

        <!-- 表单 -->
        <PopupForm />
    </div>
</template>

<script setup lang="ts">
import { onMounted, provide, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PopupForm from './popupForm.vue'
import { baTableApi } from '/@/api/common'
import { defaultOptButtons } from '/@/components/table'
import TableHeader from '/@/components/table/header/index.vue'
import Table from '/@/components/table/index.vue'
import baTableClass from '/@/utils/baTable'
import createAxios from '/@/utils/axios'
import { handleWithdraw } from '/@/api/backend/real'
defineOptions({
    name: 'withdraw',
})

const { t } = useI18n()
const tableRef = ref()
const optButtons: OptButton[] = [
    {
        // 渲染方式:tipButton=带tip的按钮,confirmButton=带确认框的按钮,moveButton=移动按钮
        render: 'confirmButton',
        // 按钮名称
        name: 'approve',
        // 鼠标放置时的 title 提示
        title: '批准提现',
        // 直接在按钮内显示的文字，title 有值时可为空
        text: '批准',
        // 按钮类型，请参考 element plus 的按钮类型
        type: 'success',
        // 按钮 icon
        icon: 'el-icon-Check',
        popconfirm: {
            title: '确认批准提现吗？',
            okText: '确认',
            cancelText: '取消',
        },
        class: 'table-row-info',
        // tipButton 禁用 tip
        disabledTip: false,
        // 自定义点击事件
        click: (row: TableRow, field: TableColumn) => {
            handleWithdraw({id:row.id,status:1}).then((res) => {
                if (res.code === 1) {
                    baTable.getIndex()
                }   
            })
        },
        // 按钮是否显示，请返回布尔值
        display: (row: TableRow, field: TableColumn) => {
            return !(row.status === 0)
        },
        // 按钮是否禁用，请返回布尔值
        disabled: (row: TableRow, field: TableColumn) => {
            return false
        },
        // 自定义el-button属性
        attr: {}
    },
    {
        // 渲染方式:tipButton=带tip的按钮,confirmButton=带确认框的按钮,moveButton=移动按钮
        render: 'confirmButton',
        // 按钮名称
        name: 'reject',
        // 鼠标放置时的 title 提示
        title: '驳回',
        // 直接在按钮内显示的文字，title 有值时可为空
        text: '驳回',
        // 按钮类型，请参考 element plus 的按钮类型
        type: 'danger',
        // 按钮 icon
        icon: 'el-icon-Close',
        class: 'table-row-info',
        // tipButton 禁用 tip
        disabledTip: false,
        popconfirm: {
            title: '确认驳回提现吗？',
            okText: '确认',
            cancelText: '取消',
        },
        // 自定义点击事件
        click: (row: TableRow, field: TableColumn) => {
            handleWithdraw({id:row.id,status:2}).then((res) => {
                if (res.code === 1) {
                    baTable.getIndex()
                }   
            })
        },
        // 按钮是否显示，请返回布尔值
        display: (row: TableRow, field: TableColumn) => {
            return !(row.status === 0)
        },
        // 按钮是否禁用，请返回布尔值
        disabled: (row: TableRow, field: TableColumn) => {
            return false
        },
        // 自定义el-button属性
        attr: {}
    }
]
// const rejectWithdraw = async (row: TableRow) => {
//     const axios = createAxios()
//     const res = await axios.post('/admin/Withdraw/reject', { id: row.id })
//     if (res.code === 200) {
//         baTable.refresh()
//     }
// }
/**
 * baTable 内包含了表格的所有数据且数据具备响应性，然后通过 provide 注入给了后代组件
 */
const baTable = new baTableClass(
    new baTableApi('/admin/Withdraw/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('withdraw.id'), prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            { label: '代理', prop: 'user.username', align: 'center', operatorPlaceholder: t('Fuzzy query'), render: 'tags', operator: 'LIKE' },
            { label: t('withdraw.money'), prop: 'money', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('withdraw.withdar_type'), prop: 'withdar_type', align: 'center', render: 'tag', operator: 'eq', sortable: false, replaceValue: { '0': t('withdraw.withdar_type 0'), '1': t('withdraw.withdar_type 1'), '2': t('withdraw.withdar_type 2'), '3': t('withdraw.withdar_type 3') } },
            { label: t('withdraw.payee'), prop: 'payee', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: t('withdraw.payee_acount'), prop: 'payee_acount', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: t('withdraw.qrcode_image'), prop: 'qrcode_image', align: 'center', render: 'image', operator: false },
            { label: t('withdraw.trx_account'), prop: 'trx_account', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: t('withdraw.status'), prop: 'status', align: 'center', render: 'tag', operator: 'eq', sortable: false, replaceValue: { '0': t('withdraw.status 0'), '1': t('withdraw.status 1'), '2': t('withdraw.status 2') },custom:{'0':'primary','1':'success','2':'danger'} },
            { label: t('withdraw.create_time'), prop: 'create_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
            { label: t('withdraw.update_time'), prop: 'update_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
            { label: t('withdraw.handle_time'), prop: 'handle_time', align: 'center', operator: 'eq', sortable: 'custom' },
            { label: t('Operate'), align: 'center', width: 200, render: 'buttons', buttons: optButtons, operator: false },
        ],
        dblClickNotEditColumn: [undefined],
    },
    {
        defaultItems: { withdar_type: '0' },
    }
)

provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getIndex()?.then(() => {
        baTable.initSort()
        baTable.dragSort()
    })
})
</script>

<style scoped lang="scss"></style>
