import createAxios from '/@/utils/axios'

export const url = '/admin/'

export function handleWithdraw(data: anyObj) {
    return createAxios(
        {
            url: url+'withdraw/handle',
            method: 'post',
            data: data,
        },
        {
            showSuccessMessage: true,
        }
    )
}

export function updateJsonApi() {
    return createAxios(
        {
            url: url+'video/updateJson',
            method: 'get',
        },
        {
            showSuccessMessage: true,
        }
    )
}

export function importVideoApi(data: FormData) {
    return createAxios(
        {
            url: url + 'video/importVideo',
            method: 'post',
            data: data,
            timeout: 30 * 1000, // 设置30秒超时
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        },
        {
            showSuccessMessage: true,
        }
    )
}

export function getUserLoginLogApi(id: number) {
    return createAxios(
        {
            url: url+'user.User/getLoginLog?user_id='+id,
            method: 'get',
        },
        {
            showSuccessMessage: true,
        }
    )
}


export function updateVideoDomainApi(domain: string) {
    return createAxios(
        {
            url: url+'video/updateVideoDomain?domain='+domain,
            method: 'get',
        },
        {
            showSuccessMessage: true,
        }
    )
}

export function updateImageDomainApi(domain: string) {
    return createAxios(
        {
            url: url+'video/updateImageDomain?domain='+domain,
            method: 'get',
        },
        {
            showSuccessMessage: true,
        }
    )
}

export function clearPayApi(id: number) {
    return createAxios(
        {
            url: url+'pay/clear?id='+id,
            method: 'get',
        },
        {
            showSuccessMessage: true,
        }
    )
}
