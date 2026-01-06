import request from '@/xxep/request';

export default {
  // 获取邀请信息
  getInviteInfo: () => 
    request({ 
      url: '/api/user/getInviteInfo', 
      method: 'GET', 
      custom: { 
        showLoading: false, 
        auth: true 
      } 
    }),
  
  // 获取团队信息
  getTeamInfo: () => 
    request({ 
      url: '/api/user/getTeamInfo', 
      method: 'GET', 
      custom: { 
        showLoading: false, 
        auth: true 
      } 
    }),
  
  // 获取团队成员列表
  getTeamMembers: (params) => 
    request({ 
      url: '/api/user/getTeamMembers', 
      method: 'GET', 
      params,
      custom: { 
        showLoading: false, 
        auth: true 
      } 
    }),
};

