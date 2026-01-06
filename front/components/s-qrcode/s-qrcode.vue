<template>
  <canvas 
    :canvas-id="canvasId" 
    :id="canvasId"
    class="s-qrcode"
    :style="canvasStyle"
  />
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';

// Props定义
const props = defineProps({
  text: { type: String, required: true, default: '' },
  size: { type: Number, default: 400 },
  background: { type: String, default: '#FFFFFF' },
  foreground: { type: String, default: '#000000' },
  canvasId: { type: String, default: 's-qrcode' }
});

// Emit定义
const emit = defineEmits(['success', 'fail']);

// 数据
const qr = ref(null);

// 计算属性
const canvasStyle = computed(() => ({
  width: `${props.size}rpx`,
  height: `${props.size}rpx`
}));

// 生成二维码
const generate = () => {
  if (!props.text) {
    emit('fail', { message: '二维码内容不能为空' });
    return;
  }
  
  try {
    // 使用正确的QRCode算法生成
    qr.value = createQRCode(props.text);
    nextTick(() => {
      drawQRCode();
      emit('success');
    });
  } catch (error) {
    console.error('[s-qrcode] 生成失败:', error);
    emit('fail', error);
  }
};

// 创建QRCode对象
const createQRCode = (text) => {
  // 选择合适的版本
  const typeNumber = getOptimalVersion(text);
  const errorCorrectionLevel = QRErrorCorrectionLevel.M;
  
  const qrcode = new QRCode(typeNumber, errorCorrectionLevel);
  qrcode.addData(text);
  qrcode.make();
  
  return qrcode;
};

// 获取最优版本
const getOptimalVersion = (text) => {
  const length = text.length;
  // M级纠错的字符容量
  if (length <= 14) return 1;  // 21x21
  if (length <= 26) return 2;  // 25x25
  if (length <= 42) return 3;  // 29x29
  if (length <= 62) return 4;  // 33x33
  if (length <= 84) return 5;  // 37x37
  if (length <= 106) return 6; // 41x41
  if (length <= 122) return 7; // 45x45
  if (length <= 152) return 8; // 49x49
  if (length <= 180) return 9; // 53x53
  return 10; // 57x57
};

// 绘制二维码到Canvas
const drawQRCode = () => {
  if (!qr.value) return;
  
  try {
    const ctx = uni.createCanvasContext(props.canvasId);
    const systemInfo = uni.getSystemInfoSync();
    
    // 计算实际像素大小
    const pixelRatio = systemInfo.pixelRatio || 1;
    const canvasSize = Math.floor(props.size / 750 * systemInfo.windowWidth);
    
    const moduleCount = qr.value.getModuleCount();
    const quietZone = 4; // 静默区（白边）为4个模块
    const totalModules = moduleCount + quietZone * 2;
    const cellSize = canvasSize / totalModules;
    
    // 清空画布并绘制背景
    ctx.fillStyle = props.background;
    ctx.fillRect(0, 0, canvasSize, canvasSize);
    
    // 绘制二维码模块
    ctx.fillStyle = props.foreground;
    
    for (let row = 0; row < moduleCount; row++) {
      for (let col = 0; col < moduleCount; col++) {
        if (qr.value.isDark(row, col)) {
          const x = (col + quietZone) * cellSize;
          const y = (row + quietZone) * cellSize;
          ctx.fillRect(x, y, cellSize, cellSize);
        }
      }
    }
    
    ctx.draw(false, () => {
      console.log('[s-qrcode] 绘制完成');
    });
  } catch (error) {
    console.error('[s-qrcode] 绘制失败:', error);
    emit('fail', error);
  }
};

// 保存到相册
const saveToAlbum = () => {
  return new Promise((resolve, reject) => {
    uni.canvasToTempFilePath({
      canvasId: props.canvasId,
      success: (res) => {
        uni.saveImageToPhotosAlbum({
          filePath: res.tempFilePath,
          success: () => resolve(res.tempFilePath),
          fail: reject
        });
      },
      fail: reject
    });
  });
};

// 获取临时文件路径
const getTempFilePath = () => {
  return new Promise((resolve, reject) => {
    uni.canvasToTempFilePath({
      canvasId: props.canvasId,
      success: (res) => resolve(res.tempFilePath),
      fail: reject
    });
  });
};

// 监听text变化
watch(() => props.text, (newVal) => {
  if (newVal) {
    generate();
  }
});

// 组件挂载
onMounted(() => {
  if (props.text) {
    nextTick(() => {
      generate();
    });
  }
});

// 暴露方法
defineExpose({
  generate,
  saveToAlbum,
  getTempFilePath
});

// ============================================
// QRCode.js - 完整可靠的实现
// ============================================

// 纠错级别
const QRErrorCorrectionLevel = {
  L: 1,
  M: 0,
  Q: 3,
  H: 2
};

// QRCode主类
class QRCode {
  constructor(typeNumber, errorCorrectionLevel) {
    this.typeNumber = typeNumber;
    this.errorCorrectionLevel = errorCorrectionLevel;
    this.modules = null;
    this.moduleCount = 0;
    this.dataCache = null;
    this.dataList = [];
  }
  
  addData(data) {
    const newData = new QR8bitByte(data);
    this.dataList.push(newData);
    this.dataCache = null;
  }
  
  isDark(row, col) {
    if (row < 0 || this.moduleCount <= row || col < 0 || this.moduleCount <= col) {
      throw new Error(`Out of bounds: ${row},${col}`);
    }
    return this.modules[row][col];
  }
  
  getModuleCount() {
    return this.moduleCount;
  }
  
  make() {
    this.makeImpl(false, this.getBestMaskPattern());
  }
  
  makeImpl(test, maskPattern) {
    this.moduleCount = this.typeNumber * 4 + 17;
    this.modules = new Array(this.moduleCount);
    
    for (let row = 0; row < this.moduleCount; row++) {
      this.modules[row] = new Array(this.moduleCount);
      for (let col = 0; col < this.moduleCount; col++) {
        this.modules[row][col] = null;
      }
    }
    
    this.setupPositionProbePattern(0, 0);
    this.setupPositionProbePattern(this.moduleCount - 7, 0);
    this.setupPositionProbePattern(0, this.moduleCount - 7);
    this.setupPositionAdjustPattern();
    this.setupTimingPattern();
    this.setupTypeInfo(test, maskPattern);
    
    if (this.typeNumber >= 7) {
      this.setupTypeNumber(test);
    }
    
    if (this.dataCache == null) {
      this.dataCache = QRCode.createData(this.typeNumber, this.errorCorrectionLevel, this.dataList);
    }
    
    this.mapData(this.dataCache, maskPattern);
  }
  
  setupPositionProbePattern(row, col) {
    for (let r = -1; r <= 7; r++) {
      if (row + r <= -1 || this.moduleCount <= row + r) continue;
      
      for (let c = -1; c <= 7; c++) {
        if (col + c <= -1 || this.moduleCount <= col + c) continue;
        
        if ((0 <= r && r <= 6 && (c == 0 || c == 6))
            || (0 <= c && c <= 6 && (r == 0 || r == 6))
            || (2 <= r && r <= 4 && 2 <= c && c <= 4)) {
          this.modules[row + r][col + c] = true;
        } else {
          this.modules[row + r][col + c] = false;
        }
      }
    }
  }
  
  getBestMaskPattern() {
    let minLostPoint = 0;
    let pattern = 0;
    
    for (let i = 0; i < 8; i++) {
      this.makeImpl(true, i);
      const lostPoint = QRUtil.getLostPoint(this);
      
      if (i == 0 || minLostPoint > lostPoint) {
        minLostPoint = lostPoint;
        pattern = i;
      }
    }
    
    return pattern;
  }
  
  setupTimingPattern() {
    for (let r = 8; r < this.moduleCount - 8; r++) {
      if (this.modules[r][6] != null) continue;
      this.modules[r][6] = (r % 2 == 0);
    }
    
    for (let c = 8; c < this.moduleCount - 8; c++) {
      if (this.modules[6][c] != null) continue;
      this.modules[6][c] = (c % 2 == 0);
    }
  }
  
  setupPositionAdjustPattern() {
    const pos = QRUtil.getPatternPosition(this.typeNumber);
    
    for (let i = 0; i < pos.length; i++) {
      for (let j = 0; j < pos.length; j++) {
        const row = pos[i];
        const col = pos[j];
        
        if (this.modules[row][col] != null) continue;
        
        for (let r = -2; r <= 2; r++) {
          for (let c = -2; c <= 2; c++) {
            if (r == -2 || r == 2 || c == -2 || c == 2 || (r == 0 && c == 0)) {
              this.modules[row + r][col + c] = true;
            } else {
              this.modules[row + r][col + c] = false;
            }
          }
        }
      }
    }
  }
  
  setupTypeNumber(test) {
    const bits = QRUtil.getBCHTypeNumber(this.typeNumber);
    
    for (let i = 0; i < 18; i++) {
      const mod = (!test && ((bits >> i) & 1) == 1);
      this.modules[Math.floor(i / 3)][i % 3 + this.moduleCount - 8 - 3] = mod;
    }
    
    for (let i = 0; i < 18; i++) {
      const mod = (!test && ((bits >> i) & 1) == 1);
      this.modules[i % 3 + this.moduleCount - 8 - 3][Math.floor(i / 3)] = mod;
    }
  }
  
  setupTypeInfo(test, maskPattern) {
    const data = (this.errorCorrectionLevel << 3) | maskPattern;
    const bits = QRUtil.getBCHTypeInfo(data);
    
    for (let i = 0; i < 15; i++) {
      const mod = (!test && ((bits >> i) & 1) == 1);
      
      if (i < 6) {
        this.modules[i][8] = mod;
      } else if (i < 8) {
        this.modules[i + 1][8] = mod;
      } else {
        this.modules[this.moduleCount - 15 + i][8] = mod;
      }
    }
    
    for (let i = 0; i < 15; i++) {
      const mod = (!test && ((bits >> i) & 1) == 1);
      
      if (i < 8) {
        this.modules[8][this.moduleCount - i - 1] = mod;
      } else if (i < 9) {
        this.modules[8][15 - i - 1 + 1] = mod;
      } else {
        this.modules[8][15 - i - 1] = mod;
      }
    }
    
    this.modules[this.moduleCount - 8][8] = (!test);
  }
  
  mapData(data, maskPattern) {
    let inc = -1;
    let row = this.moduleCount - 1;
    let bitIndex = 7;
    let byteIndex = 0;
    
    for (let col = this.moduleCount - 1; col > 0; col -= 2) {
      if (col == 6) col--;
      
      while (true) {
        for (let c = 0; c < 2; c++) {
          if (this.modules[row][col - c] == null) {
            let dark = false;
            
            if (byteIndex < data.length) {
              dark = (((data[byteIndex] >>> bitIndex) & 1) == 1);
            }
            
            const mask = QRUtil.getMask(maskPattern, row, col - c);
            
            if (mask) {
              dark = !dark;
            }
            
            this.modules[row][col - c] = dark;
            bitIndex--;
            
            if (bitIndex == -1) {
              byteIndex++;
              bitIndex = 7;
            }
          }
        }
        
        row += inc;
        
        if (row < 0 || this.moduleCount <= row) {
          row -= inc;
          inc = -inc;
          break;
        }
      }
    }
  }
  
  static createData(typeNumber, errorCorrectionLevel, dataList) {
    const rsBlocks = QRRSBlock.getRSBlocks(typeNumber, errorCorrectionLevel);
    const buffer = new QRBitBuffer();
    
    for (let i = 0; i < dataList.length; i++) {
      const data = dataList[i];
      buffer.put(data.mode, 4);
      buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
      data.write(buffer);
    }
    
    let totalDataCount = 0;
    for (let i = 0; i < rsBlocks.length; i++) {
      totalDataCount += rsBlocks[i].dataCount;
    }
    
    if (buffer.getLengthInBits() > totalDataCount * 8) {
      throw new Error(`Code length overflow. (${buffer.getLengthInBits()}>${totalDataCount * 8})`);
    }
    
    if (buffer.getLengthInBits() + 4 <= totalDataCount * 8) {
      buffer.put(0, 4);
    }
    
    while (buffer.getLengthInBits() % 8 != 0) {
      buffer.putBit(false);
    }
    
    while (true) {
      if (buffer.getLengthInBits() >= totalDataCount * 8) break;
      buffer.put(0xEC, 8);
      if (buffer.getLengthInBits() >= totalDataCount * 8) break;
      buffer.put(0x11, 8);
    }
    
    return QRCode.createBytes(buffer, rsBlocks);
  }
  
  static createBytes(buffer, rsBlocks) {
    let offset = 0;
    let maxDcCount = 0;
    let maxEcCount = 0;
    
    const dcdata = new Array(rsBlocks.length);
    const ecdata = new Array(rsBlocks.length);
    
    for (let r = 0; r < rsBlocks.length; r++) {
      const dcCount = rsBlocks[r].dataCount;
      const ecCount = rsBlocks[r].totalCount - dcCount;
      
      maxDcCount = Math.max(maxDcCount, dcCount);
      maxEcCount = Math.max(maxEcCount, ecCount);
      
      dcdata[r] = new Array(dcCount);
      
      for (let i = 0; i < dcdata[r].length; i++) {
        dcdata[r][i] = 0xff & buffer.buffer[i + offset];
      }
      offset += dcCount;
      
      const rsPoly = QRUtil.getErrorCorrectPolynomial(ecCount);
      const rawPoly = new QRPolynomial(dcdata[r], rsPoly.getLength() - 1);
      const modPoly = rawPoly.mod(rsPoly);
      
      ecdata[r] = new Array(rsPoly.getLength() - 1);
      for (let i = 0; i < ecdata[r].length; i++) {
        const modIndex = i + modPoly.getLength() - ecdata[r].length;
        ecdata[r][i] = (modIndex >= 0) ? modPoly.get(modIndex) : 0;
      }
    }
    
    let totalCodeCount = 0;
    for (let i = 0; i < rsBlocks.length; i++) {
      totalCodeCount += rsBlocks[i].totalCount;
    }
    
    const data = new Array(totalCodeCount);
    let index = 0;
    
    for (let i = 0; i < maxDcCount; i++) {
      for (let r = 0; r < rsBlocks.length; r++) {
        if (i < dcdata[r].length) {
          data[index++] = dcdata[r][i];
        }
      }
    }
    
    for (let i = 0; i < maxEcCount; i++) {
      for (let r = 0; r < rsBlocks.length; r++) {
        if (i < ecdata[r].length) {
          data[index++] = ecdata[r][i];
        }
      }
    }
    
    return data;
  }
}

// 8位字节数据
class QR8bitByte {
  constructor(data) {
    this.mode = QRMode.MODE_8BIT_BYTE;
    this.data = data;
  }
  
  getLength() {
    return this.data.length;
  }
  
  write(buffer) {
    for (let i = 0; i < this.data.length; i++) {
      buffer.put(this.data.charCodeAt(i), 8);
    }
  }
}

// 模式
const QRMode = {
  MODE_NUMBER: 1 << 0,
  MODE_ALPHA_NUM: 1 << 1,
  MODE_8BIT_BYTE: 1 << 2,
  MODE_KANJI: 1 << 3
};

// RS块
class QRRSBlock {
  constructor(totalCount, dataCount) {
    this.totalCount = totalCount;
    this.dataCount = dataCount;
  }
  
  static getRSBlocks(typeNumber, errorCorrectionLevel) {
    const rsBlock = QRRSBlock.getRsBlockTable(typeNumber, errorCorrectionLevel);
    
    if (rsBlock == undefined) {
      throw new Error(`Bad rs block @ typeNumber:${typeNumber}/errorCorrectionLevel:${errorCorrectionLevel}`);
    }
    
    const length = rsBlock.length / 3;
    const list = [];
    
    for (let i = 0; i < length; i++) {
      const count = rsBlock[i * 3 + 0];
      const totalCount = rsBlock[i * 3 + 1];
      const dataCount = rsBlock[i * 3 + 2];
      
      for (let j = 0; j < count; j++) {
        list.push(new QRRSBlock(totalCount, dataCount));
      }
    }
    
    return list;
  }
  
  static getRsBlockTable(typeNumber, errorCorrectionLevel) {
    switch (errorCorrectionLevel) {
      case QRErrorCorrectionLevel.L:
        return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 0];
      case QRErrorCorrectionLevel.M:
        return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 1];
      case QRErrorCorrectionLevel.Q:
        return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 2];
      case QRErrorCorrectionLevel.H:
        return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 3];
      default:
        return undefined;
    }
  }
}

// RS块表（版本1-10，L/M/Q/H）
QRRSBlock.RS_BLOCK_TABLE = [
  // 版本1
  [1, 26, 19], [1, 26, 16], [1, 26, 13], [1, 26, 9],
  // 版本2
  [1, 44, 34], [1, 44, 28], [1, 44, 22], [1, 44, 16],
  // 版本3
  [1, 70, 55], [1, 70, 44], [2, 35, 17], [2, 35, 13],
  // 版本4
  [1, 100, 80], [2, 50, 32], [2, 50, 24], [4, 25, 9],
  // 版本5
  [1, 134, 108], [2, 67, 43], [2, 33, 15, 2, 34, 16], [2, 33, 11, 2, 34, 12],
  // 版本6
  [2, 86, 68], [4, 43, 27], [4, 43, 19], [4, 43, 15],
  // 版本7
  [2, 98, 78], [4, 49, 31], [2, 32, 14, 4, 33, 15], [4, 39, 13, 1, 40, 14],
  // 版本8
  [2, 121, 97], [2, 60, 38, 2, 61, 39], [4, 40, 18, 2, 41, 19], [4, 40, 14, 2, 41, 15],
  // 版本9
  [2, 146, 116], [3, 58, 36, 2, 59, 37], [4, 36, 16, 4, 37, 17], [4, 36, 12, 4, 37, 13],
  // 版本10
  [2, 86, 68, 2, 87, 69], [4, 69, 43, 1, 70, 44], [6, 43, 19, 2, 44, 20], [6, 43, 15, 2, 44, 16]
];

// 位缓冲
class QRBitBuffer {
  constructor() {
    this.buffer = [];
    this.length = 0;
  }
  
  get(index) {
    const bufIndex = Math.floor(index / 8);
    return ((this.buffer[bufIndex] >>> (7 - index % 8)) & 1) == 1;
  }
  
  put(num, length) {
    for (let i = 0; i < length; i++) {
      this.putBit(((num >>> (length - i - 1)) & 1) == 1);
    }
  }
  
  getLengthInBits() {
    return this.length;
  }
  
  putBit(bit) {
    const bufIndex = Math.floor(this.length / 8);
    if (this.buffer.length <= bufIndex) {
      this.buffer.push(0);
    }
    
    if (bit) {
      this.buffer[bufIndex] |= (0x80 >>> (this.length % 8));
    }
    
    this.length++;
  }
}

// 多项式
class QRPolynomial {
  constructor(num, shift) {
    if (num.length == undefined) {
      throw new Error(num.length + "/" + shift);
    }
    
    let offset = 0;
    while (offset < num.length && num[offset] == 0) {
      offset++;
    }
    
    this.num = new Array(num.length - offset + shift);
    for (let i = 0; i < num.length - offset; i++) {
      this.num[i] = num[i + offset];
    }
  }
  
  get(index) {
    return this.num[index];
  }
  
  getLength() {
    return this.num.length;
  }
  
  multiply(e) {
    const num = new Array(this.getLength() + e.getLength() - 1);
    
    for (let i = 0; i < this.getLength(); i++) {
      for (let j = 0; j < e.getLength(); j++) {
        num[i + j] ^= QRMath.gexp(QRMath.glog(this.get(i)) + QRMath.glog(e.get(j)));
      }
    }
    
    return new QRPolynomial(num, 0);
  }
  
  mod(e) {
    if (this.getLength() - e.getLength() < 0) {
      return this;
    }
    
    const ratio = QRMath.glog(this.get(0)) - QRMath.glog(e.get(0));
    const num = new Array(this.getLength());
    
    for (let i = 0; i < this.getLength(); i++) {
      num[i] = this.get(i);
    }
    
    for (let i = 0; i < e.getLength(); i++) {
      num[i] ^= QRMath.gexp(QRMath.glog(e.get(i)) + ratio);
    }
    
    return new QRPolynomial(num, 0).mod(e);
  }
}

// 数学工具
class QRMath {
  static glog(n) {
    if (n < 1) {
      throw new Error("glog(" + n + ")");
    }
    return QRMath.LOG_TABLE[n];
  }
  
  static gexp(n) {
    while (n < 0) {
      n += 255;
    }
    while (n >= 256) {
      n -= 255;
    }
    return QRMath.EXP_TABLE[n];
  }
}

QRMath.EXP_TABLE = new Array(256);
QRMath.LOG_TABLE = new Array(256);

for (let i = 0; i < 8; i++) {
  QRMath.EXP_TABLE[i] = 1 << i;
}
for (let i = 8; i < 256; i++) {
  QRMath.EXP_TABLE[i] = QRMath.EXP_TABLE[i - 4] ^ QRMath.EXP_TABLE[i - 5] ^ QRMath.EXP_TABLE[i - 6] ^ QRMath.EXP_TABLE[i - 8];
}
for (let i = 0; i < 255; i++) {
  QRMath.LOG_TABLE[QRMath.EXP_TABLE[i]] = i;
}

// 工具类
class QRUtil {
  static getPatternPosition(typeNumber) {
    return QRUtil.PATTERN_POSITION_TABLE[typeNumber - 1];
  }
  
  static getMask(maskPattern, i, j) {
    switch (maskPattern) {
      case 0: return (i + j) % 2 == 0;
      case 1: return i % 2 == 0;
      case 2: return j % 3 == 0;
      case 3: return (i + j) % 3 == 0;
      case 4: return (Math.floor(i / 2) + Math.floor(j / 3)) % 2 == 0;
      case 5: return (i * j) % 2 + (i * j) % 3 == 0;
      case 6: return ((i * j) % 2 + (i * j) % 3) % 2 == 0;
      case 7: return ((i * j) % 3 + (i + j) % 2) % 2 == 0;
      default: throw new Error("bad maskPattern:" + maskPattern);
    }
  }
  
  static getErrorCorrectPolynomial(errorCorrectLength) {
    let a = new QRPolynomial([1], 0);
    for (let i = 0; i < errorCorrectLength; i++) {
      a = a.multiply(new QRPolynomial([1, QRMath.gexp(i)], 0));
    }
    return a;
  }
  
  static getLengthInBits(mode, type) {
    if (1 <= type && type < 10) {
      switch (mode) {
        case QRMode.MODE_NUMBER: return 10;
        case QRMode.MODE_ALPHA_NUM: return 9;
        case QRMode.MODE_8BIT_BYTE: return 8;
        case QRMode.MODE_KANJI: return 8;
        default: throw new Error("mode:" + mode);
      }
    } else if (type < 27) {
      switch (mode) {
        case QRMode.MODE_NUMBER: return 12;
        case QRMode.MODE_ALPHA_NUM: return 11;
        case QRMode.MODE_8BIT_BYTE: return 16;
        case QRMode.MODE_KANJI: return 10;
        default: throw new Error("mode:" + mode);
      }
    } else if (type < 41) {
      switch (mode) {
        case QRMode.MODE_NUMBER: return 14;
        case QRMode.MODE_ALPHA_NUM: return 13;
        case QRMode.MODE_8BIT_BYTE: return 16;
        case QRMode.MODE_KANJI: return 12;
        default: throw new Error("mode:" + mode);
      }
    } else {
      throw new Error("type:" + type);
    }
  }
  
  static getLostPoint(qrCode) {
    const moduleCount = qrCode.getModuleCount();
    let lostPoint = 0;
    
    // 评估1: 同色相邻模块
    for (let row = 0; row < moduleCount; row++) {
      for (let col = 0; col < moduleCount; col++) {
        let sameCount = 0;
        const dark = qrCode.isDark(row, col);
        
        for (let r = -1; r <= 1; r++) {
          if (row + r < 0 || moduleCount <= row + r) continue;
          for (let c = -1; c <= 1; c++) {
            if (col + c < 0 || moduleCount <= col + c) continue;
            if (r == 0 && c == 0) continue;
            if (dark == qrCode.isDark(row + r, col + c)) {
              sameCount++;
            }
          }
        }
        
        if (sameCount > 5) {
          lostPoint += (3 + sameCount - 5);
        }
      }
    }
    
    // 评估2: 2x2同色块
    for (let row = 0; row < moduleCount - 1; row++) {
      for (let col = 0; col < moduleCount - 1; col++) {
        let count = 0;
        if (qrCode.isDark(row, col)) count++;
        if (qrCode.isDark(row + 1, col)) count++;
        if (qrCode.isDark(row, col + 1)) count++;
        if (qrCode.isDark(row + 1, col + 1)) count++;
        if (count == 0 || count == 4) {
          lostPoint += 3;
        }
      }
    }
    
    // 评估3: 特定模式(1:1:3:1:1)
    for (let row = 0; row < moduleCount; row++) {
      for (let col = 0; col < moduleCount - 6; col++) {
        if (qrCode.isDark(row, col)
            && !qrCode.isDark(row, col + 1)
            && qrCode.isDark(row, col + 2)
            && qrCode.isDark(row, col + 3)
            && qrCode.isDark(row, col + 4)
            && !qrCode.isDark(row, col + 5)
            && qrCode.isDark(row, col + 6)) {
          lostPoint += 40;
        }
      }
    }
    
    for (let col = 0; col < moduleCount; col++) {
      for (let row = 0; row < moduleCount - 6; row++) {
        if (qrCode.isDark(row, col)
            && !qrCode.isDark(row + 1, col)
            && qrCode.isDark(row + 2, col)
            && qrCode.isDark(row + 3, col)
            && qrCode.isDark(row + 4, col)
            && !qrCode.isDark(row + 5, col)
            && qrCode.isDark(row + 6, col)) {
          lostPoint += 40;
        }
      }
    }
    
    // 评估4: 暗模块比例
    let darkCount = 0;
    for (let col = 0; col < moduleCount; col++) {
      for (let row = 0; row < moduleCount; row++) {
        if (qrCode.isDark(row, col)) {
          darkCount++;
        }
      }
    }
    
    const ratio = Math.abs(100 * darkCount / moduleCount / moduleCount - 50) / 5;
    lostPoint += ratio * 10;
    
    return lostPoint;
  }
  
  static getBCHTypeInfo(data) {
    let d = data << 10;
    while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(0x537) >= 0) {
      d ^= (0x537 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(0x537)));
    }
    return ((data << 10) | d) ^ 0x5412;
  }
  
  static getBCHTypeNumber(data) {
    let d = data << 12;
    while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(0x1f25) >= 0) {
      d ^= (0x1f25 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(0x1f25)));
    }
    return (data << 12) | d;
  }
  
  static getBCHDigit(data) {
    let digit = 0;
    while (data != 0) {
      digit++;
      data >>>= 1;
    }
    return digit;
  }
}

// 定位标记位置表
QRUtil.PATTERN_POSITION_TABLE = [
  [],
  [6, 18],
  [6, 22],
  [6, 26],
  [6, 30],
  [6, 34],
  [6, 22, 38],
  [6, 24, 42],
  [6, 26, 46],
  [6, 28, 50]
];
</script>

<style scoped>
.s-qrcode {
  display: block;
}
</style>

