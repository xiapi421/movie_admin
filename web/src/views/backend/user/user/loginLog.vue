<template>
    <el-dialog :title="'登录日志'" v-model="dialogVisible" width="800px">
        <el-table :data="logData" style="width: 100%" v-loading="loading">
            <el-table-column prop="ip" label="登录IP" align="center" />
            <el-table-column prop="create_time" label="登录时间" align="center">
                <template #default="scope">
                    {{ timeFormat(scope.row.create_time) }}
                </template>
            </el-table-column>
        </el-table>
    </el-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { getUserLoginLogApi } from '/@/api/backend/real'
import { timeFormat } from '/@/utils/common'


const dialogVisible = ref(false)
const loading = ref(false)
const logData = ref([])
// const siteStore = useSite()

const open = (id:number) => {
    // 在这里处理传入的行数据
    // 例如：
    // console.log(row);
    
    getUserLoginLogApi(id).then((res) => {
        logData.value = res.data
        console.log(res.data);
        
        loading.value = false
    })
    console.log('用户数据:', id)

    // 显示对话框
    dialogVisible.value = true
}

defineExpose({
    open
})


</script>
