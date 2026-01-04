import request from '@/sheep/request';

export default {
  //活动详情
  activity: (id) =>
    request({
      url: 'activity.activity/detail',
      method: 'GET',
      params: {
        id: id,
      },
    }),
  //我的拼团列表
  myGroupon: (params) =>
    request({
      url: 'activity.groupon/myGroupons',
      method: 'GET',
      params,
    }),
  //拼团列表 
  getGrouponList: (params) =>
    request({
      url: 'activity.groupon',
      method: 'GET',
      params,
    }),
  //拼团详情
  grouponDetail: (id) =>
    request({
      url: 'activity.groupon/detail',
      method: 'GET',
      params: {
        id: id,
      },
    }),
  //签到日历
  signList: (params) =>
    request({
      url: 'activity.signin',
      method: 'GET',
      params,
    }),
  //签到
  signAdd: () =>
    request({
      url: 'activity.signin/signin',
      method: 'POST',
    }),
  replenish: (data) =>
    request({
      url: 'activity.signin/replenish',
      method: 'POST',
      data,
    }),
  
};
