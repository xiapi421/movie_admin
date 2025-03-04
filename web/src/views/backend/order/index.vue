<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 统计面板 -->
        <div class="small-panel-box">
            <el-row :gutter="20">
                <el-col :sm="12" :lg="6">
                    <div class="small-panel user-reg suspension">
                        <div class="small-panel-title">今日金额</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#8595F4" size="20" name="fa fa-line-chart" />
                                <el-statistic :value="dashboard.today_sell" :value-style="statisticValueStyle" />
                            </div>
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel file suspension">
                        <div class="small-panel-title">今日单量</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#AD85F4" size="20" name="fa fa-file-text" />
                                <el-statistic :value="dashboard.today_order" :value-style="statisticValueStyle" />
                            </div>
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel users suspension">
                        <div class="small-panel-title">今日代理余额</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#74A8B5" size="20" name="fa fa-users" />
                                <el-statistic :value="dashboard.today_agent_money" :value-style="statisticValueStyle" />
                            </div>
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel addons suspension">
                        <div class="small-panel-title">今日扣量</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#F48595" size="20" name="fa fa-object-group" />
                                <el-statistic :value="dashboard.today_kl" :value-style="statisticValueStyle" />
                            </div>
                        </div>
                    </div>
                </el-col>
            </el-row>
        </div>
        <div class="small-panel-box">
            <el-row :gutter="20">
                <el-col :sm="12" :lg="6">
                    <div class="small-panel user-reg suspension">
                        <div class="small-panel-title">昨日金额</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#8595F4" size="20" name="fa fa-line-chart" />
                                <el-statistic :value="dashboard.last_sell" :value-style="statisticValueStyle" />
                            </div>
                            <!-- <div class="content-right">+14%</div> -->
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel file suspension">
                        <div class="small-panel-title">昨日单量</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#AD85F4" size="20" name="fa fa-file-text" />
                                <el-statistic :value="dashboard.last_order" :value-style="statisticValueStyle" />
                            </div>
                            <!-- <div class="content-right">+50%</div> -->
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel users suspension">
                        <div class="small-panel-title">昨日代理余额</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#74A8B5" size="20" name="fa fa-users" />
                                <el-statistic :value="dashboard.last_agent_money" :value-style="statisticValueStyle" />
                            </div>
                            <!-- <div class="content-right">+28%</div> -->
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel addons suspension">
                        <div class="small-panel-title">昨日扣量</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#F48595" size="20" name="fa fa-object-group" />
                                <el-statistic :value="dashboard.last_kl" :value-style="statisticValueStyle" />
                            </div>
                            <!-- <div class="content-right">+88%</div> -->
                        </div>
                    </div>
                </el-col>
            </el-row>
        </div>
        <!-- 表格顶部菜单 -->
        <!-- 自定义按钮请使用插槽，甚至公共搜索也可以使用具名插槽渲染，参见文档 -->
        <!-- <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('order.quick Search Fields') })"
        ></TableHeader> -->
        <TableHeader
            :buttons="['refresh', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('order.quick Search Fields') })"
        >
            <template #default>
                <el-button v-blur class="table-header-operate" type="success" @click="fetchDashboardData()" style="margin-left: 10px;">
                    <Icon color="#ffffff" name="el-icon-RefreshRight" />
                    <span class="table-header-operate-text">手动刷新</span>
                </el-button>
            </template>
        </TableHeader>

        <!-- 表格 -->
        <!-- 表格列有多种自定义渲染方式，比如自定义组件、具名插槽等，参见文档 -->
        <!-- 要使用 el-table 组件原有的属性，直接加在 Table 标签上即可 -->
        <Table ref="tableRef"></Table>

        <!-- 表单 -->
        <PopupForm />
    </div>
</template>

<script setup lang="ts">
import { onMounted, provide, ref, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { CSSProperties } from 'vue'
import PopupForm from './popupForm.vue'
import { baTableApi } from '/@/api/common'
import { defaultOptButtons } from '/@/components/table'
import TableHeader from '/@/components/table/header/index.vue'
import Table from '/@/components/table/index.vue'
import baTableClass from '/@/utils/baTable'
import { index } from '/@/api/backend/dashboard'
import Icon from '/@/components/icon/index.vue'

defineOptions({
    name: 'order',
})

const { t } = useI18n()
const tableRef = ref()
const optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])
let timer: number // 定时器变量

// 统计面板数据
interface DashboardData {
    today_sell: number
    today_order: number
    last_sell: number
    last_order: number
    today_agent_money: number
    last_agent_money: number
    today_kl: number
    last_kl: number
}

const dashboard = ref<DashboardData>({
    today_sell: 0,
    today_order: 0,
    last_sell: 0,
    last_order: 0,
    today_agent_money: 0,
    last_agent_money: 0,
    today_kl: 0,
    last_kl: 0
})

const statisticValueStyle: CSSProperties = {
    fontSize: '28px',
}

// 获取统计数据
const fetchDashboardData = () => {
    index().then((res) => {
        dashboard.value.today_sell = res.data.today_sell
        dashboard.value.today_order = res.data.today_order
        dashboard.value.last_sell = res.data.last_sell
        dashboard.value.last_order = res.data.last_order
        dashboard.value.today_agent_money = res.data.today_agent_money
        dashboard.value.last_agent_money = res.data.last_agent_money
        dashboard.value.today_kl = res.data.today_kl
        dashboard.value.last_kl = res.data.last_kl
    })

    baTable.getIndex()?.then(() => {
        baTable.initSort()
        baTable.dragSort()
    })
}

/**
 * baTable 内包含了表格的所有数据且数据具备响应性，然后通过 provide 注入给了后代组件
 */
const baTable = new baTableClass(
    new baTableApi('/admin/Order/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('order.id'), prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            { label: t('order.order_sn'), prop: 'order_sn', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: t('order.out_order_sn'), prop: 'out_order_sn', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: t('order.ip'), prop: 'ip', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: t('order.user__username'), prop: 'user.username', align: 'center', operatorPlaceholder: t('Fuzzy query'), render: 'tags', operator: 'LIKE' },
            { label: t('order.video_id'), prop: 'video_id', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('order.money'), prop: 'money', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('order.subscribe_type'), prop: 'subscribe_type', align: 'center', render: 'tag', operator: 'eq', sortable: false, replaceValue: { single: t('order.subscribe_type single'), day: t('order.subscribe_type day'), week: t('order.subscribe_type week'), month: t('order.subscribe_type month') } },
            { label: t('order.pay_id'), prop: 'pay_id', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('order.status'), prop: 'status', align: 'center', render: 'tag', operator: 'eq', sortable: false, replaceValue: { '0': t('order.status 0'), '1': t('order.status 1'), '2': t('order.status 2') },custom:{'0':'primary','1':'success','2':'danger'} },
            { label: t('order.create_time'), prop: 'create_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
            // { label: t('order.update_time'), prop: 'update_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
            { label: t('order.notify_time'), prop: 'notify_time', align: 'center', render: 'datetime',operator: 'RANGE', sortable: 'custom' ,width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss'},
            { label: t('Operate'), align: 'center', width: 100, render: 'buttons', buttons: optButtons, operator: false },
        ],
        dblClickNotEditColumn: [undefined, 'status'],
    },
    {
        defaultItems: { subscribe_type: 'single' },
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
    
    // 初始加载统计数据
    fetchDashboardData()
    // 每分钟刷新一次数据
    timer = window.setInterval(fetchDashboardData, 10000)
})

onUnmounted(() => {
    // 组件卸载时清除定时器
    clearInterval(timer)
})
</script>

<style scoped lang="scss">
.small-panel-box {
    margin-bottom: 20px;
}
.small-panel {
    background-color: #e9edf2;
    border-radius: var(--el-border-radius-base);
    padding: 25px;
    margin-bottom: 20px;
    .small-panel-title {
        color: #92969a;
        font-size: 15px;
    }
    .small-panel-content {
        display: flex;
        align-items: flex-end;
        margin-top: 20px;
        color: #2c3f5d;
        .content-left {
            display: flex;
            align-items: center;
            font-size: 24px;
            .icon {
                margin-right: 10px;
            }
        }
    }
}

html.dark {
    .small-panel {
        background-color: var(--ba-bg-color-overlay);
        .small-panel-content {
            color: var(--el-text-color-regular);
        }
    }
}
</style>
