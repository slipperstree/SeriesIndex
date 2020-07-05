<?php
/**
 * 根据文章标题自动生成系列文章的目录，方便给读者导航
 * 
 * @package Series Index
 * @author mango
 * @version 0.1.0
 * @link https://github.com/slipperstree
 */

 // 使用方法：
 // 在文章某处地方加上<!-- series-index -->，程序会把这个注释替换成目录树

 // 样式：
 // .index-menu			整个目录
 // .index-menu-list	列表 ul
 // .index-menu-item	每个目录项 li
 // .index-menu-link	目录项连接 a

class SeriesIndex_Plugin implements Typecho_Plugin_Interface {
	
	/**
	 * 索引ID
	 */
	public static $id = 1;
	
	public static $pattern = '/(&lt;|<)!--\s*series-index\s*--(&gt;|>)/i';

	/**
	 * 目录树
	 */
	public static $tree = array();

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('SeriesIndex_Plugin', 'contentEx');
        //Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('SeriesIndex_Plugin', 'excerptEx');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {}
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 列表页忽略目录生成标记
     * 
     * @access public
     * @return string
     */
    public static function excerptEx( $html, $widget, $lastResult){
        return preg_replace(self::$pattern,'',$html);
    }

    /**
     * 内容页构造索引目录
     * 
     * @access public
     * @return string
     */
    public static function contentEx( $html, $widget, $lastResult ) {
        $html = empty( $lastResult ) ? $html : $lastResult;

		// 文章标题：$widget->title  形如：开源代码分析学习 - 有趣的聊天机器人 - 01
		// 文章链接：$widget->permalink  形如：http://blog.mangolovecarrot.net/2020/07/01/chatbot01/
		// 文章slug：$widget->slug  形如：chatbot01

		// 先取slug末尾的数字（不论几位都可以）
		$slug = $widget->slug;
		$slugLastDigitCnt = 0;
		while ($slugLastDigitCnt < strlen($slug) && is_numeric(substr($slug, strlen($slug)-($slugLastDigitCnt+1)))) {
			$slugLastDigitCnt++;
		}

		if ($slugLastDigitCnt > 0 && $slugLastDigitCnt < strlen($slug)) {
			// slug末尾有数字，并且不全为数字的话就假定除了数字的部分的前缀是系列的slug前缀
			// 将slug前缀作为检索条件在contents表中查询同一个系列的文章（标题? 内容?里面第一个# 或 ## 或 ###的文字）
			$slugPrefix = substr($slug, 0, strlen($slug)-$slugLastDigitCnt);

			$db = Typecho_Db::get();

			$seriesContents = $db->fetchAll($db
            ->select()->from('table.contents')
			->where('table.contents.slug like ?', $slugPrefix . '%')
			->where('table.contents.type = ?', 'post')
			->order('table.contents.slug', Typecho_Db::SORT_ASC));

			// 如果只有一篇，表示除了当前这篇文章以外没有相同slug前缀的文章，不生成系列文章
			if (count($seriesContents) <= 1) {
				return $html;
			}
			
			$seriesIndexHtml = "<div style='padding-left:10px; padding-top:10px; padding-bottom:10px; border:0px solid blue; background:#EDEFED; border-left:3px solid #D2D7D2;'>";
			//$seriesIndexHtml = "<div style='border:0px solid blue;background:#EDEFED;'>";
			$seriesIndexHtml .= "<h3>系列文章</h3>";
			$seriesIndexHtml .= "<ul>";
			foreach ($seriesContents as $seriesContent) {
				// TODO 取到以后拼接出url，并用上面取出的文字作为链接文字
				$url = self::getURL($seriesContent);
				//$url = "#";

				if ($seriesContent['slug'] == $slug) {
					// 当前文章不加link
					$seriesIndexHtml .= "<li><b>" . $seriesContent['title'] . "</b>【当前文章】</li>";
				} else {
					$seriesIndexHtml .= "<li><a href='" . $url . "'  title='" . $seriesContent['title'] . "'>" . $seriesContent['title'] . "</a></li>";
				}
			}
			$seriesIndexHtml .= "</ul>";
			$seriesIndexHtml .= "</div>";

			// 在最后添加系列目录（也可允许替换 <!-- series-index --> )

			// 文章末尾添加模式
			return $html . $seriesIndexHtml;

			// 替换 <!-- series-index --> 模式
			//return preg_replace( self::$pattern, '<div class="index-menu">' . $seriesIndexHtml . '</div>', $html );
		} else {

			// slug不符合规则，不做任何处理
			return $html;
		}
	}

	public static function getURL($dbContentRow) {
		$value = array();
		//return "#";
		$value['date'] = new Typecho_Date($dbContentRow['created']);

        /** 生成日期 */
        $value['year'] = $value['date']->year;
        $value['month'] = $value['date']->month;
		$value['day'] = $value['date']->day;
		$value['slug'] = urlencode($dbContentRow['slug']);
		$value['category'] = urlencode($dbContentRow['category']);
		
		$pathinfo = Typecho_Router::url("post", $value);
		return Typecho_Common::url($pathinfo, $widget->options->index);
	}
	
	// 获取系列文章列表
	// TODO
	protected function getSeriesList($column, $offset, $type, $status = NULL, $authorId = 0, $pageSize = 20)
    {
        $select = $this->db->select(array('COUNT(table.contents.cid)' => 'num'))->from('table.contents')
        ->where("table.contents.{$column} > {$offset}")
        ->where("table.contents.type = ?", $type);

        if (!empty($status)) {
            $select->where("table.contents.status = ?", $status);
        }

        if ($authorId > 0) {
            $select->where('table.contents.authorId = ?', $authorId);
        }

        $count = $this->db->fetchObject($select)->num + 1;
        return ceil($count / $pageSize);
    }

    /**
     * 解析
     * 
     * @access public
     * @param array $matches 解析值
     * @return string
     */
    public static function parseCallback( $match ) {
		$parent = &self::$tree;

		$html = $match[0];
		$n = $match[1];
		$menu = array(
			'num' => $n,
			'title' => trim( strip_tags( $html ) ),
			'id' => 'menu_index_' . self::$id,
			'sub' => array()
		);
		$current = array();
		if( $parent ) {
			$current = &$parent[ count( $parent ) - 1 ];
		}
		// 根
		if( ! $parent || ( isset( $current['num'] ) && $n <= $current['num'] ) ) {
			$parent[] = $menu;
		} else {
			while( is_array( $current[ 'sub' ] ) ) {
				// 父子关系
				if( $current['num'] == $n - 1 ) {
					$current[ 'sub' ][] = $menu;
					break;
				}
				// 后代关系，并存在子菜单
				elseif( $current['num'] < $n && $current[ 'sub' ] ) {
					$current = &$current['sub'][ count( $current['sub'] ) - 1 ];
				}
				// 后代关系，不存在子菜单
				else {
					for( $i = 0; $i < $n - $current['num']; $i++ ) {
						$current['sub'][] = array(
							'num' => $current['num'] + 1,
							'sub' => array()
						);
						$current = &$current['sub'][0];
					}
					$current['sub'][] = $menu;
					break;
				}
			}
		}
		self::$id++;
		return "<span class=\"menu-target-fix\" id=\"{$menu['id']}\" name=\"{$menu['id']}\"></span>" . $html;
	}
	/**
     * 构建目录树，生成索引
     * 
     * @access public
     * @return string
     */
	public static function buildMenuHtml( $tree, $include = true ) {
		$menuHtml = '';
		foreach( $tree as $menu ) {
			if( ! isset( $menu['id'] ) && $menu['sub'] ) {
				$menuHtml .= self::buildMenuHtml( $menu['sub'], false );
			} elseif( $menu['sub'] ) {
				$menuHtml .= "<li class=\"index-menu-item\"><a data-scroll class=\"index-menu-link\" href=\"#{$menu['id']}\" title=\"{$menu['title']}\">{$menu['title']}</a>" . self::buildMenuHtml( $menu['sub'] ) . "</li>";
			} else {
				$menuHtml .= "<li class=\"index-menu-item\"><a data-scroll class=\"index-menu-link\" href=\"#{$menu['id']}\" title=\"{$menu['title']}\">{$menu['title']}</a></li>";
			}
		}
		if( $include ) {
			$menuHtml = '<ul class="index-menu-list">' . $menuHtml . '</ul>';
		}
		return $menuHtml;
	}
}
