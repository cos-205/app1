# deploy.sh SSH 密钥上传分析

## 问题分析

### 当前配置
在 `deploy.sh` 中，开发环境配置为：
```bash
DEV_SERVER_IP="192.238.206.76:41181"  # IP:端口格式
```

### 问题所在

**第153行的 `setup_ssh_key()` 函数存在问题：**

```142:161:shop/deploy.sh
# 设置SSH密钥认证
setup_ssh_key() {
    echo -e "${YELLOW}开始设置SSH密钥认证到 ${SERVER_USER}@${SERVER_IP}...${NC}"

    # 检查密钥是否存在，若不存在则创建
    if [ ! -f "$SSH_KEY" ]; then
        echo -e "${YELLOW}未找到SSH密钥，正在创建新密钥...${NC}"
        ssh-keygen -t rsa -b 4096 -f "$SSH_KEY" -N ""
    fi

    # 上传公钥到服务器
    echo -e "${YELLOW}正在上传公钥到服务器，请输入服务器密码...${NC}"
    ssh-copy-id -i "${SSH_KEY}.pub" "${SERVER_USER}@${SERVER_IP}"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}SSH密钥认证设置成功！您现在可以无密码部署了。${NC}"
    else
        echo -e "${RED}SSH密钥认证设置失败！${NC}"
        exit 1
    fi
}
```

**问题：**
1. `ssh-copy-id` 命令不支持 `IP:PORT` 格式
2. 当 `SERVER_IP` 包含端口号（如 `192.238.206.76:41181`）时，`ssh-copy-id` 会失败
3. 需要使用 `-p` 参数指定端口，但需要先分离 IP 和端口

### 其他SSH命令的处理

脚本中其他SSH命令（如 `ssh`、`scp`、`rsync`）都使用了 `-e "ssh -i $SSH_KEY"` 或 `-i "$SSH_KEY"`，但也没有处理端口号。

例如：
- 第166行：`ssh -i "$SSH_KEY" "${SERVER_USER}@${SERVER_IP}"`
- 第221行：`scp -i "$SSH_KEY" "$2" "${SERVER_USER}@${SERVER_IP}:${TMP_FILE}"`
- 第943行：`rsync -avz ... -e "ssh -i $SSH_KEY -o ConnectTimeout=30"`

这些命令在 `SERVER_IP` 包含端口号时都会失败。

## 解决方案

### 方案1：修改脚本支持端口分离（推荐）

需要修改脚本，将 IP 和端口分离处理：

```bash
# 解析服务器IP和端口
parse_server_info() {
    if [[ "$SERVER_IP" == *":"* ]]; then
        # 包含端口号
        SERVER_HOST="${SERVER_IP%%:*}"
        SERVER_PORT="${SERVER_IP##*:}"
    else
        # 默认22端口
        SERVER_HOST="$SERVER_IP"
        SERVER_PORT="22"
    fi
}

# 修改 setup_ssh_key() 函数
setup_ssh_key() {
    parse_server_info
    
    echo -e "${YELLOW}开始设置SSH密钥认证到 ${SERVER_USER}@${SERVER_HOST}:${SERVER_PORT}...${NC}"

    # 检查密钥是否存在，若不存在则创建
    if [ ! -f "$SSH_KEY" ]; then
        echo -e "${YELLOW}未找到SSH密钥，正在创建新密钥...${NC}"
        ssh-keygen -t rsa -b 4096 -f "$SSH_KEY" -N ""
    fi

    # 上传公钥到服务器（指定端口）
    echo -e "${YELLOW}正在上传公钥到服务器，请输入服务器密码...${NC}"
    if [ "$SERVER_PORT" != "22" ]; then
        ssh-copy-id -i "${SSH_KEY}.pub" -p "$SERVER_PORT" "${SERVER_USER}@${SERVER_HOST}"
    else
        ssh-copy-id -i "${SSH_KEY}.pub" "${SERVER_USER}@${SERVER_HOST}"
    fi

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}SSH密钥认证设置成功！您现在可以无密码部署了。${NC}"
    else
        echo -e "${RED}SSH密钥认证设置失败！${NC}"
        exit 1
    fi
}
```

### 方案2：使用 SSH 配置文件

在 `~/.ssh/config` 中配置服务器信息：

```
Host dev-server
    HostName 192.238.206.76
    Port 41181
    User root
    IdentityFile ~/.ssh/id_rsa
```

然后修改脚本使用别名：
```bash
SERVER_IP="dev-server"  # 使用SSH配置中的别名
```

### 方案3：手动上传密钥（临时方案）

如果脚本暂时无法修改，可以手动执行：

```bash
# 1. 生成密钥（如果还没有）
ssh-keygen -t rsa -b 4096 -f ~/.ssh/id_rsa -N ""

# 2. 手动上传公钥到服务器（指定端口）
ssh-copy-id -i ~/.ssh/id_rsa.pub -p 41181 root@192.238.206.76

# 或者手动复制公钥内容
cat ~/.ssh/id_rsa.pub | ssh -p 41181 root@192.238.206.76 "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"
```

## 需要修改的地方

1. **添加端口解析函数**：在脚本开头添加 `parse_server_info()` 函数
2. **修改 `setup_ssh_key()`**：支持端口参数
3. **修改 `test_ssh_connection()`**：支持端口参数
4. **修改所有 SSH 命令**：在 `ssh`、`scp`、`rsync` 命令中添加 `-p` 参数
5. **修改所有 SSH 连接字符串**：使用分离后的 `SERVER_HOST` 和 `SERVER_PORT`

## 具体修改示例

### 修改 SSH 连接命令

**原代码：**
```bash
ssh -i "$SSH_KEY" "${SERVER_USER}@${SERVER_IP}" "echo 连接成功"
```

**修改后：**
```bash
if [ "$SERVER_PORT" != "22" ]; then
    ssh -i "$SSH_KEY" -p "$SERVER_PORT" "${SERVER_USER}@${SERVER_HOST}" "echo 连接成功"
else
    ssh -i "$SSH_KEY" "${SERVER_USER}@${SERVER_HOST}" "echo 连接成功"
fi
```

### 修改 SCP 命令

**原代码：**
```bash
scp -i "$SSH_KEY" "$2" "${SERVER_USER}@${SERVER_IP}:${TMP_FILE}"
```

**修改后：**
```bash
if [ "$SERVER_PORT" != "22" ]; then
    scp -i "$SSH_KEY" -P "$SERVER_PORT" "$2" "${SERVER_USER}@${SERVER_HOST}:${TMP_FILE}"
else
    scp -i "$SSH_KEY" "$2" "${SERVER_USER}@${SERVER_HOST}:${TMP_FILE}"
fi
```

**注意：** `scp` 使用 `-P`（大写），而 `ssh` 使用 `-p`（小写）

### 修改 RSYNC 命令

**原代码：**
```bash
rsync -avz -e "ssh -i $SSH_KEY" "$dir/" "${SERVER_USER}@${SERVER_IP}:${REMOTE_DIR}/$dir/"
```

**修改后：**
```bash
if [ "$SERVER_PORT" != "22" ]; then
    rsync -avz -e "ssh -i $SSH_KEY -p $SERVER_PORT" "$dir/" "${SERVER_USER}@${SERVER_HOST}:${REMOTE_DIR}/$dir/"
else
    rsync -avz -e "ssh -i $SSH_KEY" "$dir/" "${SERVER_USER}@${SERVER_HOST}:${REMOTE_DIR}/$dir/"
fi
```

## 总结

当前脚本**不支持非22端口的SSH连接**。需要：

1. 添加端口解析逻辑
2. 在所有SSH相关命令中添加端口参数
3. 确保 `ssh-copy-id` 使用 `-p` 参数指定端口

建议采用**方案1**，修改脚本以支持端口分离，这样更灵活且不需要修改SSH配置文件。

